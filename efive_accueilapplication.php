<?php

/**
 * Copyright since 2007 PrestaShop SA and Contributors
 * PrestaShop is an International Registered Trademark & Property of PrestaShop SA
 *
 * NOTICE OF LICENSE
 *
 * This source file is subject to the Academic Free License 3.0 (AFL-3.0)
 * that is bundled with this package in the file LICENSE.md.
 * It is also available through the world-wide-web at this URL:
 * https://opensource.org/licenses/AFL-3.0
 * If you did not receive a copy of the license and are unable to
 * obtain it through the world-wide-web, please send an email
 * to license@prestashop.com so we can send you a copy immediately.
 *
 * DISCLAIMER
 *
 * Do not edit or add to this file if you wish to upgrade PrestaShop to newer
 * versions in the future. If you wish to customize PrestaShop for your
 * needs please refer to https://devdocs.prestashop.com/ for more information.
 *
 * @author    PrestaShop SA and Contributors <contact@prestashop.com>
 * @copyright Since 2007 PrestaShop SA and Contributors
 * @license   https://opensource.org/licenses/AFL-3.0 Academic Free License 3.0 (AFL-3.0)
 */
if (!defined('_PS_VERSION_')) {
    exit;
}

use PrestaShop\PrestaShop\Core\Module\WidgetInterface;

class Efive_AccueilApplication extends Module implements WidgetInterface
{
    private $templateFile;

    public function __construct()
    {
        $this->name = 'efive_accueilapplication';
        $this->tab = 'front_office_features';
        $this->version = '1.0.0';
        $this->author = 'Valentin HUARD';
        $this->need_instance = 0;

        $this->bootstrap = true;
        parent::__construct();

        $this->displayName = $this->trans('Application section for Element5', [], 'Modules.Efive.Admin');
        $this->description = $this->trans('Add an application section to the homepage to highlight the products in a visual and friendly way.', [], 'Modules.Efive.Admin');

        $this->ps_versions_compliancy = ['min' => '1.7.1.0', 'max' => _PS_VERSION_];

        $this->templateFile = 'module:efive_accueilapplication/efive_accueilapplication.tpl';
    }

    public function install()
    {
        return parent::install() &&
            $this->registerHook('displayHome') &&
            $this->registerHook('actionObjectLanguageAddAfter') &&
            $this->registerHook('header') &&
            $this->installFixtures();
    }

    public function hookActionObjectLanguageAddAfter($params)
    {
        return $this->installFixture((int) $params['object']->id, Configuration::get('ACCUEIL_APPLICATION_METRE_IMG', (int) Configuration::get('PS_LANG_DEFAULT')),
        Configuration::get('ACCUEIL_APPLICATION_D4P_IMG', (int) Configuration::get('PS_LANG_DEFAULT')));
    }

    /**
     * Add the CSS to the head of the page.
     */
    public function hookHeader()
    {
        $this->context->controller->addCSS($this->_path . 'views/css/style.css', 'all');
    }

    protected function installFixtures()
    {
        $languages = Language::getLanguages(false);

        foreach ($languages as $lang) {
            $this->installFixture((int) $lang['id_lang'], 'metre.jpg', 'd4p.png');
        }

        return true;
    }

    protected function installFixture($id_lang, $image = null, $image_2 = null)
    {
        $values['ACCUEIL_APPLICATION_METRE_IMG'][(int) $id_lang] = $image;
        $values['ACCUEIL_APPLICATION_D4P_IMG'][(int) $id_lang] = $image_2;
        $values['ACCUEIL_APPLICATION_METRE_DESC'][(int) $id_lang] = '';
        $values['ACCUEIL_APPLICATION_D4P_DESC'][(int) $id_lang] = '';

        Configuration::updateValue('ACCUEIL_APPLICATION_METRE_IMG', $values['ACCUEIL_APPLICATION_METRE_IMG']);
        Configuration::updateValue('ACCUEIL_APPLICATION_D4P_IMG', $values['ACCUEIL_APPLICATION_D4P_IMG']);
        Configuration::updateValue('ACCUEIL_APPLICATION_METRE_DESC', $values['ACCUEIL_APPLICATION_METRE_DESC']);
        Configuration::updateValue('ACCUEIL_APPLICATION_D4P_DESC', $values['ACCUEIL_APPLICATION_D4P_DESC']);
    }

    public function uninstall()
    {
        Configuration::deleteByName('ACCUEIL_APPLICATION_METRE_IMG');
        Configuration::deleteByName('ACCUEIL_APPLICATION_D4P_IMG');
        Configuration::deleteByName('ACCUEIL_APPLICATION_METRE_DESC');
        Configuration::deleteByName('ACCUEIL_APPLICATION_D4P_DESC');

        return parent::uninstall();
    }

    public function postProcess()
    {
        if (Tools::isSubmit('submitApplicationConf')) {
            $languages = Language::getLanguages(false);
            $values = [];
            $update_images_values = false;

            foreach ($languages as $lang) {
                if (
                    isset($_FILES['ACCUEIL_APPLICATION_METRE_IMG_' . $lang['id_lang']])
                    && isset($_FILES['ACCUEIL_APPLICATION_METRE_IMG_' . $lang['id_lang']]['tmp_name'])
                    && !empty($_FILES['ACCUEIL_APPLICATION_METRE_IMG_' . $lang['id_lang']]['tmp_name'])
                    && isset($_FILES['ACCUEIL_APPLICATION_D4P_IMG_' . $lang['id_lang']])
                    && isset($_FILES['ACCUEIL_APPLICATION_D4P_IMG_' . $lang['id_lang']]['tmp_name'])
                    && !empty($_FILES['ACCUEIL_APPLICATION_D4P_IMG_' . $lang['id_lang']]['tmp_name'])
                ) {
                    if (($error = ImageManager::validateUpload($_FILES['ACCUEIL_APPLICATION_METRE_IMG_' . $lang['id_lang']], 4000000)) || ($error = ImageManager::validateUpload($_FILES['ACCUEIL_APPLICATION_D4P_IMG_' . $lang['id_lang']], 4000000))) {
                        return $this->displayError($error);
                    } else {
                        $ext = substr($_FILES['ACCUEIL_APPLICATION_METRE_IMG_' . $lang['id_lang']]['name'], strrpos($_FILES['ACCUEIL_APPLICATION_METRE_IMG_' . $lang['id_lang']]['name'], '.') + 1);
                        $file_name = md5($_FILES['ACCUEIL_APPLICATION_METRE_IMG_' . $lang['id_lang']]['name']) . '.' . $ext;

                        $ext2 = substr($_FILES['ACCUEIL_APPLICATION_D4P_IMG_' . $lang['id_lang']]['name'], strrpos($_FILES['ACCUEIL_APPLICATION_D4P_IMG_' . $lang['id_lang']]['name'], '.') + 1);
                        $file_name2 = md5($_FILES['ACCUEIL_APPLICATION_D4P_IMG_' . $lang['id_lang']]['name']) . '.' . $ext2;

                        if (!move_uploaded_file($_FILES['ACCUEIL_APPLICATION_METRE_IMG_' . $lang['id_lang']]['tmp_name'], dirname(__FILE__) . DIRECTORY_SEPARATOR . 'img' . DIRECTORY_SEPARATOR . $file_name)) {
                            return $this->displayError($this->trans('An error occurred while attempting to upload the file.', [], 'Admin.Notifications.Error'));
                        } else {
                            if (
                                Configuration::hasContext('ACCUEIL_APPLICATION_METRE_IMG', $lang['id_lang'], Shop::getContext())
                                && Configuration::get('ACCUEIL_APPLICATION_METRE_IMG', $lang['id_lang']) != $file_name
                            ) {
                                @unlink(dirname(__FILE__) . DIRECTORY_SEPARATOR . 'img' . DIRECTORY_SEPARATOR . Configuration::get('ACCUEIL_APPLICATION_METRE_IMG', $lang['id_lang']));
                            }

                            $values['ACCUEIL_APPLICATION_METRE_IMG'][$lang['id_lang']] = $file_name;
                        }

                        if (!move_uploaded_file($_FILES['ACCUEIL_APPLICATION_D4P_IMG_' . $lang['id_lang']]['tmp_name'], dirname(__FILE__) . DIRECTORY_SEPARATOR . 'img' . DIRECTORY_SEPARATOR . $file_name2)) {
                            return $this->displayError($this->trans('An error occurred while attempting to upload the file.', [], 'Admin.Notifications.Error'));
                        } else {
                            if (
                                Configuration::hasContext('ACCUEIL_APPLICATION_D4P_IMG', $lang['id_lang'], Shop::getContext())
                                && Configuration::get('ACCUEIL_APPLICATION_D4P_IMG', $lang['id_lang']) != $file_name2
                            ) {
                                @unlink(dirname(__FILE__) . DIRECTORY_SEPARATOR . 'img' . DIRECTORY_SEPARATOR . Configuration::get('ACCUEIL_APPLICATION_D4P_IMG', $lang['id_lang']));
                            }

                            $values['ACCUEIL_APPLICATION_D4P_IMG'][$lang['id_lang']] = $file_name2;
                        }
                    }

                    $update_images_values = true;
                }

                $values['ACCUEIL_APPLICATION_METRE_DESC'][$lang['id_lang']] = Tools::getValue('ACCUEIL_APPLICATION_METRE_DESC_' . $lang['id_lang']);
                $values['ACCUEIL_APPLICATION_D4P_DESC'][$lang['id_lang']] = Tools::getValue('ACCUEIL_APPLICATION_D4P_DESC_' . $lang['id_lang']);
            }

            if ($update_images_values && isset($values['ACCUEIL_APPLICATION_METRE_IMG']) && isset($values['ACCUEIL_APPLICATION_D4P_IMG'])) {
                Configuration::updateValue('ACCUEIL_APPLICATION_METRE_IMG', $values['ACCUEIL_APPLICATION_METRE_IMG'], true);
                Configuration::updateValue('ACCUEIL_APPLICATION_D4P_IMG', $values['ACCUEIL_APPLICATION_D4P_IMG'], true);
            }

            Configuration::updateValue('ACCUEIL_APPLICATION_METRE_DESC', $values['ACCUEIL_APPLICATION_METRE_DESC'], true);
            Configuration::updateValue('ACCUEIL_APPLICATION_D4P_DESC', $values['ACCUEIL_APPLICATION_D4P_DESC'], true);

            $this->_clearCache($this->templateFile);

            return $this->displayConfirmation($this->trans('The settings have been updated.', [], 'Admin.Notifications.Success'));
        }

        return '';
    }

    public function getContent()
    {
        return $this->postProcess() . $this->renderForm();
    }

    public function renderForm()
    {
        $fields_form = [
            'form' => [
                'legend' => [
                    'title' => $this->trans('Settings', [], 'Admin.Global'),
                    'icon' => 'icon-cogs',
                ],
                'input' => [
                    [
                        'type' => 'file_lang',
                        'label' => $this->trans('First block image', [], 'Modules.Efive.Admin'),
                        'name' => 'ACCUEIL_APPLICATION_METRE_IMG',
                        'desc' => $this->trans('Upload an image for the first block. The recommanded ratio of the image is 16:9', [], 'Modules.Efive.Admin'),
                        'lang' => true,
                    ],
                    [
                        'type' => 'textarea',
                        'cols' => 40,
                        'rows' => 10,
                        'class' => 'rte',
                        'autoload_rte' => true,
                        'lang' => true,
                        'label' => $this->trans('First block description', [], 'Modules.Efive.Admin'),
                        'name' => 'ACCUEIL_APPLICATION_METRE_DESC',
                        'desc' => $this->trans('Please enter a meaningful description for the first block', [], 'Modules.Efive.Admin'),
                    ],
                    [
                        'type' => 'file_lang',
                        'label' => $this->trans('Second block image', [], 'Modules.Efive.Admin'),
                        'name' => 'ACCUEIL_APPLICATION_D4P_IMG',
                        'desc' => $this->trans('Upload an image for the second block. The recommanded ratio of the image is 16:9', [], 'Modules.Efive.Admin'),
                        'lang' => true,
                    ],
                    [
                        'type' => 'textarea',
                        'cols' => 40,
                        'rows' => 10,
                        'class' => 'rte',
                        'autoload_rte' => true,
                        'lang' => true,
                        'label' => $this->trans('Third block description', [], 'Modules.Efive.Admin'),
                        'name' => 'ACCUEIL_APPLICATION_D4P_DESC',
                        'desc' => $this->trans('Please enter a meaningful description for the second block', [], 'Modules.Efive.Admin'),
                    ],
                ],
                'submit' => [
                    'title' => $this->trans('Save', [], 'Admin.Actions'),
                ],
            ],
        ];

        $lang = new Language((int) Configuration::get('PS_LANG_DEFAULT'));

        $helper = new HelperForm();
        $helper->show_toolbar = false;
        $helper->table = $this->table;
        $helper->default_form_language = $lang->id;
        $helper->module = $this;
        $helper->allow_employee_form_lang = Configuration::get('PS_BO_ALLOW_EMPLOYEE_FORM_LANG') ? Configuration::get('PS_BO_ALLOW_EMPLOYEE_FORM_LANG') : 0;
        $helper->identifier = $this->identifier;
        $helper->submit_action = 'submitApplicationConf';
        $helper->currentIndex = $this->context->link->getAdminLink('AdminModules', false) . '&configure=' . $this->name . '&tab_module=' . $this->tab . '&module_name=' . $this->name;
        $helper->token = Tools::getAdminTokenLite('AdminModules');
        $helper->tpl_vars = [
            'uri' => $this->getPathUri(),
            'fields_value' => $this->getConfigFieldsValues(),
            'languages' => $this->context->controller->getLanguages(),
            'id_language' => $this->context->language->id,
        ];

        return $helper->generateForm([$fields_form]);
    }

    public function getConfigFieldsValues()
    {
        $languages = Language::getLanguages(false);
        $fields = [];

        foreach ($languages as $lang) {
            $fields['ACCUEIL_APPLICATION_METRE_IMG'][$lang['id_lang']] = Tools::getValue('ACCUEIL_IMG_' . $lang['id_lang'], Configuration::get('ACCUEIL_APPLICATION_METRE_IMG', $lang['id_lang']));
            $fields['ACCUEIL_APPLICATION_METRE_DESC'][$lang['id_lang']] = Tools::getValue('ACCUEIL_DESC_' . $lang['id_lang'], Configuration::get('ACCUEIL_APPLICATION_METRE_DESC', $lang['id_lang']));
            $fields['ACCUEIL_APPLICATION_D4P_IMG'][$lang['id_lang']] = Tools::getValue('ACCUEIL_IMG_' . $lang['id_lang'], Configuration::get('ACCUEIL_APPLICATION_D4P_IMG', $lang['id_lang']));
            $fields['ACCUEIL_APPLICATION_D4P_DESC'][$lang['id_lang']] = Tools::getValue('ACCUEIL_DESC_' . $lang['id_lang'], Configuration::get('ACCUEIL_APPLICATION_D4P_DESC', $lang['id_lang']));
        }

        return $fields;
    }

    public function renderWidget($hookName, array $params)
    {
        if (!$this->isCached($this->templateFile, $this->getCacheId('efive_accueilapplication'))) {
            $this->smarty->assign($this->getWidgetVariables($hookName, $params));
        }

        return $this->fetch($this->templateFile, $this->getCacheId('efive_accueilapplication'));
    }

    public function getWidgetVariables($hookName, array $params)
    {
        $imgname = Configuration::get('ACCUEIL_APPLICATION_METRE_IMG', $this->context->language->id);
        $imgname2 = Configuration::get('ACCUEIL_APPLICATION_D4P_IMG', $this->context->language->id);
        $imgDir = _PS_MODULE_DIR_ . $this->name . DIRECTORY_SEPARATOR . 'img' . DIRECTORY_SEPARATOR . $imgname;
        $imgDir2 = _PS_MODULE_DIR_ . $this->name . DIRECTORY_SEPARATOR . 'img' . DIRECTORY_SEPARATOR . $imgname2;

        if ($imgname && file_exists($imgDir) && $imgname2 && file_exists($imgDir2)) {
            $sizes = getimagesize($imgDir);
            $sizes2 = getimagesize($imgDir2);

            $this->smarty->assign([
                'banner_img' => $this->context->link->protocol_content . Tools::getMediaServer($imgname) . $this->_path . 'img/' . $imgname,
                'banner_width' => $sizes[0],
                'banner_height' => $sizes[1],
                'banner_img2' => $this->context->link->protocol_content . Tools::getMediaServer($imgname2) . $this->_path . 'img/' . $imgname2,
                'banner_width2' => $sizes2[0],
                'banner_height2' => $sizes2[1],
            ]);
        }

        return [
            'metre_desc' => Configuration::get('ACCUEIL_APPLICATION_METRE_DESC', $this->context->language->id),
            'd4p_desc' => Configuration::get('ACCUEIL_APPLICATION_D4P_DESC', $this->context->language->id),
        ];
    }
}