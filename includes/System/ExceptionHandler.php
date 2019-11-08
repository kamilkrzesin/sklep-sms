<?php
namespace App\System;

use App\Exceptions\EntityNotFoundException;
use App\Exceptions\LicenseException;
use App\Exceptions\LicenseRequestException;
use App\Exceptions\RequireInstallationException;
use App\Exceptions\SqlQueryException;
use App\Exceptions\ValidationException;
use App\Http\Responses\ApiResponse;
use App\Translation\TranslationManager;
use App\Translation\Translator;
use Exception;
use Raven_Client;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

class ExceptionHandler implements ExceptionHandlerContract
{
    /** @var Application */
    private $app;

    /** @var Translator */
    private $lang;

    /** @var Path */
    private $path;

    private $dontReport = [
        RequireInstallationException::class,
        LicenseException::class,
        ValidationException::class,
    ];

    public function __construct(
        Application $app,
        Path $path,
        TranslationManager $translationManager
    ) {
        $this->app = $app;
        $this->lang = $translationManager->user();
        $this->path = $path;
    }

    public function render(Request $request, Exception $e)
    {
        if ($this->app->isDebug()) {
            $exceptionDetails = $this->getExceptionDetails($e);

            return new JsonResponse($exceptionDetails);
        }

        if ($e instanceof LicenseException) {
            return new Response($this->lang->translate('verification_error'));
        }

        if ($e instanceof LicenseRequestException) {
            return new Response($e->getMessage());
        }

        if ($e instanceof EntityNotFoundException) {
            return new Response($e->getMessage(), 404);
        }

        if ($e instanceof ValidationException) {
            return new ApiResponse(
                "warnings",
                $this->lang->translate('form_wrong_filled'),
                false,
                array_merge(
                    [
                        "warnings" => format_warnings($e->warnings),
                    ],
                    $e->data
                )
            );
        }

        if ($e instanceof RequireInstallationException) {
            return new RedirectResponse('/setup');
        }

        return new Response(
            'Coś się popsuło. Więcej informacji znajdziesz w pliku data/logs/errors.log'
        );
    }

    public function report(Exception $e)
    {
        if (!$this->shouldReport($e)) {
            return;
        }

        $exceptionDetails = $this->getExceptionDetails($e);

        log_error(json_encode($exceptionDetails, JSON_PRETTY_PRINT));

        if ($this->app->bound(Raven_Client::class)) {
            $this->reportToSentry($e);
        }

        if ($e instanceof SqlQueryException) {
            $this->reportSqlException($e);
        }
    }

    protected function reportToSentry(Exception $e)
    {
        /** @var Raven_Client $client */
        $client = $this->app->make(Raven_Client::class);
        $client->captureException($e);
    }

    protected function getExceptionDetails(Exception $e)
    {
        return [
            'message' => $e->getMessage(),
            'file' => $e->getFile(),
            'line' => $e->getLine(),
            'code' => $e->getCode(),
            'trace' => $e->getTrace(),
        ];
    }

    protected function reportSqlException(SqlQueryException $e)
    {
        if (strlen($e->getQuery())) {
            log_to_file($this->path->sqlLogPath(), $e->getQuery(false));
        }
    }

    protected function shouldReport(Exception $e)
    {
        foreach ($this->dontReport as $type) {
            if ($e instanceof $type) {
                return false;
            }
        }

        return true;
    }
}