<?php

require_once $_SERVER['DOCUMENT_ROOT'] . '/bitrix/modules/main/install/wizard/wizard.php';

/**
 * Class Installer
 */
class Installer
{
    /**
     * Installer constructor.
     */
    public function __construct()
    {
        $this->deleteDefaultInstallation();
    }

    /**
     * @param string $value
     */
    private function setCurrentStepID($value)
    {
        $this->setRequestParam('CurrentStepID', $value);
    }

    /**
     * @param string $value
     */
    private function setNextStepID($value)
    {
        $this->setRequestParam('NextStepID', $value);
    }

    /**
     * @param string $value
     */
    private function setPreviousStepID($value)
    {
        $this->setRequestParam('PreviousStepID', $value);
    }

    /**
     * @param string $key
     * @param mixed $value
     * @return self
     */
    private function setRequestParam($key, $value)
    {
        $_REQUEST[$key] = $value;
        $_POST[$key] = $value;

        return $this;
    }

    /**
     * Execute installation step
     * @return void
     */
    protected function request()
    {
        ob_start();

        $this->setParams();

        $steps = array(
            CheckLicenseKey::class,
            DBTypeStep::class,
            RequirementStep::class,
            CreateDBStep::class,
            ExtendedCreateModulesStep::class,
            CreateAdminStep::class,
            SelectWizardStep::class,
            LoadModuleStep::class,
            LoadModuleActionStep::class,
            SelectWizard1Step::class
        );

        $wizard = new CWizardBase(
            str_replace("#VERS#", SM_VERSION, InstallGetMessage("INS_TITLE")),
            $package = null
        );

        $wizard->AddSteps($steps);
        $wizard->SetReturnOutput(true);
        $wizard->Display();

        ob_clean();
    }

    /**
     * Set request params for installation steps
     * @return void
     */
    private function setParams()
    {
        $params = array(
            '__wiz_agree_license' => 'Y',
            '__wiz_dbType' => 'mysql',
            '__wiz_lic_key_variant' => '',
            '__wiz_utf8' => 'Y',
            '__wiz_host' => 'mysql',
            '__wiz_create_user' => 'N',
            '__wiz_user' => 'bitrix',
            '__wiz_password' => 'bitrix',
            '__wiz_create_database' => 'N',
            '__wiz_database' => 'bitrix',
            '__wiz_create_database_type' => 'innodb',
            '__wiz_root_user' => '',
            '__wiz_root_password' => '',
            '__wiz_file_access_perms' => '0644',
            '__wiz_folder_access_perms' => '0755',
            '__wiz_login' => 'admin',
            '__wiz_admin_password' => 'admin123',
            '__wiz_admin_password_confirm' => 'admin123',
            '__wiz_email' => 'admin@mail.com',
            '__wiz_user_name' => '',
            '__wiz_user_surname' => '',
            '__wiz_selected_wizard' => 'bitrix.eshop:bitrix:eshop',
        );

        foreach ($params as $code => $param) {
            $this->setRequestParam($code, $param);
        }
    }

    /**
     * Step of select database type
     * @return self
     */
    public function dbTypeStep()
    {
        $this->setCurrentStepID('select_database');
        $this->setNextStepID('requirements');

        $this->request();

        $this->println('Selected database type');

        return $this;
    }

    /**
     * Requirements step
     * @return self
     */
    public function requirementStep()
    {
        $this->setCurrentStepID('requirements');
        $this->setNextStepID('create_database');

        $this->request();

        $this->println('Requirements step');

        return $this;
    }

    /**
     * Create database step
     * @return self
     */
    public function createDBStep()
    {
        $this->setCurrentStepID('create_database');
        $this->setNextStepID('create_modules');

        $this->request();

        $this->println('Database setup');

        return $this;
    }

    /**
     * Installation modules step
     * @param bool $isMain
     * @return self
     */
    public function createModulesStep($isMain = false)
    {
        $threeSteps = array(
            'utf8',
            'database',
            'files'
        );

        if ($isMain) {
            $modules = array(
                'main' => $threeSteps
            );
        } else {
            $modules = array(
                'abtest' => $threeSteps,
                'bitrix.eshop' => $threeSteps,
                'catalog' => $threeSteps,
                'compression' => $threeSteps,
                'conversion' => $threeSteps,
                'currency' => $threeSteps,
                'fileman' => $threeSteps,
                'form' => $threeSteps,
                'highloadblock' => $threeSteps,
                'iblock' => $threeSteps,
                'pull' => $threeSteps,
                'rest' => $threeSteps,
                'sale' => $threeSteps,
                'scale' => $threeSteps,
                'search' => $threeSteps,
                'security' => $threeSteps,
                'sender' => $threeSteps,
                'storeassist' => $threeSteps,
                'translate' => $threeSteps,
                'ui' => $threeSteps,
                'remove_mysql' => array(
                    array('single')
                ),
                'remove_mssql' => array(
                    array('single')
                ),
                'remove_oracle' => array(
                    array('single')
                ),
                'remove_misc' => array(
                    array('single')
                ),
                '__finish' => array(
                    array('single')
                )
            );
        }

        $this->setCurrentStepID('create_modules');

        foreach ($modules as $module => $steps) {
            foreach ($steps as $step) {
                $this->setRequestParam('__wiz_nextStep', $module);
                $this->setRequestParam('__wiz_nextStepStage', $step);

                $this->request();

                $this->println(sprintf('%s module install, step %s', $module, $step));
            }
        }

        return $this;
    }

    /**
     * Create admin interface step
     * @return self
     */
    public function createAdminStep()
    {
        $this->setCurrentStepID('create_admin');

        $this->request();

        $this->println('Setup admin');

        return $this;
    }

    /**
     * Load modules step
     * @return self
     */
    public function createLoadModuleStep()
    {
        $this->setCurrentStepID('load_module');

        $this->request();

        $this->println('Load modules');

        return $this;
    }

    /**
     * Load modules action step
     * @return self
     */
    public function createLoadModuleActionStep()
    {
        $this->setCurrentStepID('load_module_action');

        $this->request();

        $this->println('Load modules action');

        return $this;
    }

    /**
     * Finish install step
     * @return self
     */
    public function createFinishStep()
    {
        $this->setCurrentStepID('finish');

        $this->request();

        $this->println('Installation finish');

        return $this;
    }

    /**
     * Remove code for web install
     * @return void
     */
    private function deleteDefaultInstallation()
    {
        $data = file_get_contents($_SERVER['DOCUMENT_ROOT'] . '/bitrix/modules/main/install/wizard/wizard.php');
        $newData = preg_replace('/\$wizard= new CWizardBase.+$/', '', $data);
        file_put_contents($_SERVER['DOCUMENT_ROOT'] . '/bitrix/modules/main/install/wizard/wizard.php', $newData);
    }

    /**
     * @param string $string
     * @return void
     */
    private function println($string)
    {
        print($string);
        print(PHP_EOL);
    }
}
