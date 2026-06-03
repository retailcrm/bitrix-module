<?php

namespace Intaro\RetailCrm\Controller;

use Bitrix\Main\Engine\ActionFilter\Authentication;
use Bitrix\Main\Engine\ActionFilter\Csrf;
use Bitrix\Main\Engine\ActionFilter\HttpMethod;
use Bitrix\Main\Engine\Controller;
use Intaro\RetailCrm\Component\ConfigProvider;
use Intaro\RetailCrm\Component\Constants;
use Intaro\RetailCrm\Repository\TemplateRepository;
use CAgent;
/**
 * @category Integration
 * @package  Intaro\RetailCrm\Controller
 * @author   RetailCRM <integration@retailcrm.ru>
 * @license  MIT
 * @link     http://retailcrm.ru
 * @see      http://retailcrm.ru/docs
 */
class AdminPanel extends Controller
{
    public function configureActions(): array
    {
        return [
            'createTemplate' => [
                'prefilters' => [
                    new Authentication(),
                    new HttpMethod([HttpMethod::METHOD_POST]),
                    new Csrf(),
                ],
            ],
            'createSaleTemplate' => [
                'prefilters' => [
                    new Authentication(),
                    new HttpMethod([HttpMethod::METHOD_POST]),
                    new Csrf(),
                ],
            ],
            'updateIds' => [
                'prefilters' => [
                    new Authentication(),
                    new HttpMethod([HttpMethod::METHOD_POST]),
                    new Csrf(),
                ],
            ],
            'loyaltyProgramToggle' => [
                'prefilters' => [
                    new Authentication(),
                    new HttpMethod([HttpMethod::METHOD_POST]),
                    new Csrf(),
                ],
            ],
        ];
    }

    /**
     * @param array  $templates
     * @param string $donor
     * @param string $replaceDefaultTemplate
     *
     * @return array
     */
    public function createTemplateAction(array $templates, string $donor, string $replaceDefaultTemplate = 'N'): array
    {
        if (!$this->hasAdminAccess()) {
            return [
                'status' => false,
            ];
        }

        $templateName = $replaceDefaultTemplate === 'Y' ? '.default' : Constants::DEFAULT_LOYALTY_TEMPLATE;
        $pathFrom = $this->getComponentSourcePath($donor);

        if ($pathFrom === null) {
            return [
                'status' => false,
            ];
        }

        $status = false;

        foreach ($templates as $template) {
            $templateRoot = $this->getTemplateRootFromInput($template);

            if ($templateRoot === null) {
                continue;
            }

            $pathTo = $templateRoot
                . '/components/bitrix/'
                . $donor
                . '/'
                . $templateName;

            if ($replaceDefaultTemplate === 'Y' && file_exists($pathTo)) {
                $backPath = $pathTo . '_backup';

                 CopyDirFiles(
                    $pathTo,
                    $backPath,
                    true,
                    true,
                    false
                );
            }

            $status = CopyDirFiles(
                $pathFrom,
                $pathTo,
                true,
                true,
                false
            );
        }

        return [
            'status' => $status ?? false,
        ];
    }

    /**
     * @return array
     */
    public function updateIdsAction(): array
    {
        if (!$this->hasAdminAccess()) {
            return ['success' => false];
        }

        $agentName = 'RetailCrmUser::updateLoyaltyAccountIdsAgent();';

        CAgent::RemoveAgent($agentName, 'intaro.retailcrm');
    
        $agentId = CAgent::AddAgent(
            $agentName,
            'intaro.retailcrm',
            'N',
            20,
            '',
            'Y',
            date('d.m.Y H:i:s', time() + 10),
            30
        );

        return ['success' => $agentId !== false];
    }

    /**
     * @return string[]
     * @throws \Bitrix\Main\ArgumentOutOfRangeException
     */
    public function LoyaltyProgramToggleAction(): array
    {
        if (!$this->hasAdminAccess()) {
            return ['newStatus' => ConfigProvider::getLoyaltyProgramStatus()];
        }

        $status    = ConfigProvider::getLoyaltyProgramStatus();
        $newStatus = $status !== 'Y' ? 'Y' : 'N';
        ConfigProvider::setLoyaltyProgramStatus($newStatus);

        return ['newStatus' => $newStatus];
    }

    /**
     * @param       $templates
     * @param string $defreplace
     * @return array
     */
    public function createSaleTemplateAction($templates, $defreplace = 'N'): array
    {
        if (!$this->hasAdminAccess()) {
            return [
                'status' => false,
            ];
        }

        $templateName = $defreplace === 'Y' ? '.default' : Constants::MODULE_ID;
        $pathFrom = $this->getSaleTemplateSourcePath();
        $status = false;

        if ($pathFrom === null) {
            return [
                'status' => false,
            ];
        }

        foreach ($templates as $template) {
            $templateRoot = $this->getTemplateRootFromInput($template);

            if ($templateRoot === null) {
                continue;
            }

            $pathTo = $templateRoot
                . '/components/bitrix/sale.order.ajax/'
                . $templateName;

            if ($defreplace === 'Y' && file_exists($pathTo)) {
                $backPath = $pathTo . '_backup';

                 CopyDirFiles(
                    $pathTo,
                    $backPath,
                    true,
                    true,
                    false
                );
            }

            $status = CopyDirFiles(
                $pathFrom,
                $pathTo,
                true,
                true,
                false
            );
        }

        return [
            'status' => $status,
        ];
    }

    private function hasAdminAccess(): bool
    {
        global $APPLICATION, $USER;

        return $USER instanceof \CUser
            && $APPLICATION instanceof \CMain
            && ($USER->IsAdmin() || $APPLICATION->GetGroupRight(Constants::MODULE_ID) === 'W');
    }

    private function getComponentSourcePath(string $donor): ?string
    {
        $allowedDonors = ['sale.order.ajax', 'main.register', 'sale.basket.basket'];

        if (!in_array($donor, $allowedDonors, true)) {
            return null;
        }

        $componentsRoot = realpath(
            $_SERVER['DOCUMENT_ROOT']
            . '/bitrix/modules/'
            . Constants::MODULE_ID
            . '/install/export/local/components/intaro'
        );
        $pathFrom = realpath(
            $_SERVER['DOCUMENT_ROOT']
            . '/bitrix/modules/'
            . Constants::MODULE_ID
            . '/install/export/local/components/intaro/' . $donor . '/templates/.default'
        );

        if ($componentsRoot === false || $pathFrom === false) {
            return null;
        }

        return $this->isPathInside($pathFrom, $componentsRoot) ? $pathFrom : null;
    }

    private function getSaleTemplateSourcePath(): ?string
    {
        $componentsRoot = realpath(
            $_SERVER['DOCUMENT_ROOT']
            . '/bitrix/modules/'
            . Constants::MODULE_ID
            . '/install/export/local/components/intaro'
        );
        $pathFrom = realpath(
            $_SERVER['DOCUMENT_ROOT']
            . '/bitrix/modules/'
            . Constants::MODULE_ID
            . '/install/export/local/components/intaro/sale.order.ajax/templates/.default'
        );

        if ($componentsRoot === false || $pathFrom === false) {
            return null;
        }

        return $this->isPathInside($pathFrom, $componentsRoot) ? $pathFrom : null;
    }

    /**
     * @param mixed $template
     */
    private function getTemplateRootFromInput($template): ?string
    {
        if (is_array($template)) {
            $location = (string) ($template['location'] ?? '');
            $name = (string) ($template['name'] ?? '');

            return $this->resolveTemplateRoot($location, $name);
        }

        if (is_string($template)) {
            foreach ([TemplateRepository::LOCAL_TEMPLATE_DIR, TemplateRepository::BITRIX_TEMPLATE_DIR] as $location) {
                $templateRoot = $this->resolveTemplateRoot($location, $template);

                if ($templateRoot !== null) {
                    return $templateRoot;
                }
            }
        }

        return null;
    }

    private function resolveTemplateRoot(string $location, string $name): ?string
    {
        if (!in_array($location, [TemplateRepository::LOCAL_TEMPLATE_DIR, TemplateRepository::BITRIX_TEMPLATE_DIR], true)) {
            return null;
        }

        if (!preg_match('/^[A-Za-z0-9._-]+$/', $name)) {
            return null;
        }

        $allowedRoot = realpath($_SERVER['DOCUMENT_ROOT'] . $location);
        $templateRoot = realpath($_SERVER['DOCUMENT_ROOT'] . $location . $name);

        if ($allowedRoot === false || $templateRoot === false) {
            return null;
        }

        return $this->isPathInside($templateRoot, $allowedRoot) ? $templateRoot : null;
    }

    private function isPathInside(string $path, string $root): bool
    {
        $normalizedPath = rtrim(str_replace('\\', '/', $path), '/');
        $normalizedRoot = rtrim(str_replace('\\', '/', $root), '/');

        return $normalizedPath === $normalizedRoot
            || strpos($normalizedPath, $normalizedRoot . '/') === 0;
    }
}
