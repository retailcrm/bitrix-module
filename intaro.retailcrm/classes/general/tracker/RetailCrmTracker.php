<?php

use Bitrix\Main\Page\Asset;

/**
 * Class RetailCrmTracker
 *
 * @category RetailCRM
 * @package RetailCRM\Tracker
 */
class RetailCrmTracker
{
    public static function add()
    {
        if (!RetailcrmConfigProvider::isEventTrackerEnabled()
            && !RetailcrmConfigProvider::isOnlineConsultantEnabled()
            && RetailcrmConfigProvider::getOnlineConsultantScript() === ''
        ) {
            return;
        }

        CJSCore::RegisterExt('tracker', [
            'js' => '/local/js/tracker.js',
            'rel' => []
        ]);

        $events = [];

        if (RetailcrmConfigProvider::isEventTrackerCartEnabled()) {
            $events[] = 'cart';
        }

        if (RetailcrmConfigProvider::isEventTrackerOpenCartEnabled()) {
            $events[] = 'open_cart';
        }

        if ($events === []) {
            return;
        }

        AddEventHandler('main', 'OnEpilog', function() use ($events) {
            CJSCore::Init(['tracker']);

            Asset::getInstance()->addString(
                '<script>
            BX.ready(function() {
                if (typeof window.startTrack === "function") {
                    startTrack(...' . json_encode($events) . ');
                }
            });
        </script>'
            );
        });
    }
}
