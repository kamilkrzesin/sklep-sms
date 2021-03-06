<?php
namespace App\Http\Controllers\View;

use App\Html\UnescapedSimpleText;
use App\Routes\UrlGenerator;
use App\System\CurrentPage;
use App\System\Heart;
use App\System\License;
use App\System\Template;
use App\Translation\TranslationManager;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

class ExtraStuffController
{
    public function action(
        Request $request,
        Template $template,
        Heart $heart,
        CurrentPage $currentPage,
        TranslationManager $translationManager,
        UrlGenerator $url,
        License $license
    ) {
        $lang = $translationManager->user();

        // Jezeli jest popup, to wyswietl info w nowym oknie
        $popup = $request->query->get("action");
        if ($popup) {
            // Usuwamy napis popup z linku
            $link = preg_replace(
                '/' . preg_quote("&popup={$popup}", '/') . '$/',
                '',
                $request->server->get('REQUEST_URI')
            );

            $safeLink = str_replace('"', '\"', $link);
            $output = create_dom_element(
                "script",
                new UnescapedSimpleText(
                    'window.open("' . $safeLink . '", "", "height=720,width=1280");'
                ),
                [
                    'type' => "text/javascript",
                ]
            );

            return new Response($output);
        }

        $action = $request->query->get("action");

        switch ($action) {
            case "service_long_description":
                $output = "";
                $heart->pageTitle = $lang->translate('description') . ": ";
                $service = $request->query->get("service");

                $serviceModule = $heart->getServiceModule($service);
                if ($serviceModule) {
                    $output = $serviceModule->descriptionLongGet();
                    $heart->pageTitle .= $serviceModule->service->getName();
                }

                $heart->styleAdd($url->versioned("build/css/static/extra_stuff/long_desc.css"));
                $header = $template->render("header", compact('currentPage', 'heart', 'license'));

                $output = create_dom_element("html", [
                    create_dom_element("head", $header),
                    create_dom_element("body", $output),
                ]);

                return new Response($output);
        }

        return new Response();
    }
}
