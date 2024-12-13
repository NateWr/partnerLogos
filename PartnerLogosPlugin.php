<?php

namespace APP\plugins\generic\partnerLogos;

use APP\core\Application;
use APP\template\TemplateManager;
use PKP\components\forms\context\PKPAppearanceAdvancedForm;
use PKP\components\forms\context\PKPAppearanceSetupForm;
use PKP\components\forms\context\PKPMastheadForm;
use PKP\components\forms\Field;
use PKP\components\forms\FieldPreparedContent;
use PKP\components\forms\FieldRichTextarea;
use PKP\components\forms\FormComponent;
use PKP\context\Context;
use PKP\context\LibraryFile;
use PKP\context\LibraryFileDAO;
use PKP\db\DAORegistry;
use PKP\plugins\GenericPlugin;
use PKP\plugins\Hook;

class PartnerLogosPlugin extends GenericPlugin
{

    public const LIBRARY_FILE_TYPE_PARTNER = 0x00101;
    public const VARIABLE = 'partnerLogos';

    public function getDisplayName()
    {
        return __('plugins.generic.partnerLogos.displayName');
    }

    public function getDescription()
    {
        return __('plugins.generic.partnerLogos.description');
    }

    public function register($category, $path, $mainContextId = null)
    {
        if (!parent::register($category, $path, $mainContextId)) {
            return false;
        }
        Hook::add('PublisherLibrary::types::names', [$this, 'addFileTypeName']);
        Hook::add('PublisherLibrary::types::titles', [$this, 'addFileTypeTitle']);
        Hook::add('PublisherLibrary::types::suffixes', [$this, 'addFileTypeSuffix']);
        Hook::add('Form::config::before', [$this, 'addPreparedContent']);
        Hook::add('TemplateManager::fetch', [$this, 'addPreparedContentToNavItem']);
        Hook::add('TemplateManager::display', [$this, 'renderLogosInTemplates']);

        return true;
    }

    /**
     * Add a slug for the new file type
     */
    public function addFileTypeName(string $hookName, array $args): bool
    {
        $names = &$args[0];
        $names[self::LIBRARY_FILE_TYPE_PARTNER] = 'partners';
        return false;
    }

    /**
     * Add a title for the new file type
     */
    public function addFileTypeTitle(string $hookName, array $args): bool
    {
        $names = &$args[0];
        $names[self::LIBRARY_FILE_TYPE_PARTNER] = 'plugins.generic.partnerLogos.fileType';
        return false;
    }

    /**
     * Add a suffix for the new file type
     */
    public function addFileTypeSuffix(string $hookName, array $args): bool
    {
        $names = &$args[0];
        $names[self::LIBRARY_FILE_TYPE_PARTNER] = 'PAR';
        return false;
    }

    /**
     * Get all files in the Partner category of the Publisher Library
     */
    public function getFiles(int $contextId): array
    {
        static $files = [];

        if (!count($files)) {
            /** @var LibraryFileDAO $libraryFileDao */
            $libraryFileDao = DAORegistry::getDAO('LibraryFileDAO');
            $files = collect($libraryFileDao->getByContextId($contextId, self::LIBRARY_FILE_TYPE_PARTNER)->toArray())
                ->filter(fn(LibraryFile $libraryFile) => $libraryFile->getPublicAccess())
                ->values()
                ->toArray();
        }

        return $files;
    }

    /**
     * Get the rendered template with the partner logos.
     */
    public function getHtml(Context $context): string
    {
        $templateMgr = TemplateManager::getManager(Application::get()->getRequest());
        $templateMgr->assign([
            'partnerLogos' => $this->getFiles($context->getId()),
        ]);
        return $templateMgr->fetch($this->getTemplateResource('logos.tpl'));
    }

    /**
     * Replace {$partnerLogos} with the output HTML in a string
     */
    public function renderLogos(string $input, Context $context): string
    {
        $search = preg_quote($this->getPlaceholder());
        return preg_replace("/{$search}/", $this->getHtml($context), $input);
    }

    /**
     * Add prepared content to some form fields so that users
     * can insert the logos through the TinyMCE editor.
     *
     * This only works on newer FormComponent forms.
     */
    public function addPreparedContent(string $hookName, FormComponent $form): bool
    {
        $request = Application::get()->getRequest();
        $context = $request->getContext();

        if (!$context) {
            return false;
        }

        $targetFields = collect([
            ['form' => PKPMastheadForm::class, 'field' => 'editorialTeam'],
            ['form' => PKPMastheadForm::class, 'field' => 'about'],
            ['form' => PKPAppearanceSetupForm::class, 'field' => 'pageFooter'],
            ['form' => PKPAppearanceAdvancedForm::class, 'field' => 'additionalHomeContent'],
        ]);

        $targetFields->each(function (array $targetField) use ($form) {
            if (!is_a($form, $targetField['form'])) {
                return;
            }
            $form->fields = array_map(
                function(Field $field, int $i) use ($targetField) {
                    if (!is_a($field, FieldRichTextarea::class) || $field->name !== $targetField['field']) {
                        return $field;
                    }

                    /**
                     * Convert to a FieldPreparedContent so that we can
                     * add the Insert Content button
                     */
                    $properties = get_object_vars($field);
                    unset($properties['component']);
                    $newField = new FieldPreparedContent($field->name, $properties);

                    if (!isset($newField->preparedContent) && !is_array($newField->preparedContent)) {
                        $newField->preparedContent = [];
                    }

                    $newField->preparedContent[] = [
                        'key' => self::VARIABLE,
                        'description' => __('plugins.generic.partnerLogos.insertContent.description'),
                        'value' => '{$' . self::VARIABLE . '}',
                    ];

                    return $newField;
                },
                $form->fields,
                array_keys($form->fields)
            );
        });

        return false;
    }

    /**
     * Add prepared content to the TinyMCE editor for custom
     * navigation menu pages.
     */
    public function addPreparedContentToNavItem(string $hookName, array $args): bool
    {
        $templateMgr = $args[0];
        $template = $args[1];

        if ($template !== 'controllers/grid/navigationMenus/form/navigationMenuItemsForm.tpl') {
            return false;
        }

        $templateMgr->assign([
            'allowedVariables' => array_merge(
                (array) $templateMgr->getTemplateVars('allowedVariables'),
                [
                    self::VARIABLE => $this->getPlaceholderLabel(),
                ]
            ),
        ]);

        return false;
    }

    /**
     * Render the partner logos in templates
     *
     * This method makes the necessary changes in a few template variables
     * in order to transform the {$partnerLogos} placeholder into the list
     * of logos.
     */
    public function renderLogosInTemplates(string $hookName, array $args): bool
    {
        $templateMgr = $args[0];
        $template = $args[1];

        $context = $templateMgr->getTemplateVars('currentContext');
        if (!$context) {
            return false;
        }

        if (substr($template, 0, 14) === 'frontend/pages') {
            $this->modifyGlobalTemplateVariables($context, $templateMgr);
        }

        if ($template === 'frontend/pages/navigationMenuItemViewContent.tpl') {
            $this->modifyCustomNavItem($context, $templateMgr);
            return false;
        }

        return false;
    }

    /**
     * Get the {$partnerLogos} placeholder string
     */
    protected function getPlaceholder(): string
    {
        return '{$' . self::VARIABLE . '}';
    }

    /**
     * Get a translation with the placeholder text to show
     * when inserted into rich text editors
     */
    protected function getPlaceholderLabel(): string
    {
        return '[' . __('plugins.generic.partnerLogos.displayName') . ']';
    }

    /**
     * Modify common global template variables
     *
     * @see self::renderLogosInTemplates
     */
    protected function modifyGlobalTemplateVariables(Context $context, TemplateManager $templateMgr): void
    {
        $contextData = collect([
            'about',
            'additionalHomeContent',
            'editorialTeam',
            'pageFooter',
        ]);

        $contextData->each(function (string $key) use ($context) {
            $newValue = [];
            foreach ((array) $context->getLocalizedData($key) as $locale => $value) {
                $newValue[$locale] = str_contains($value, $this->getPlaceholder())
                    ? $this->renderLogos((string) $value, $context)
                    : $value;
            }
            $context->_data[$key] = $newValue;
        });

        // Update variables that have already been assigned to
        // the TemplateManager
        $templateVars = [
            'additionalHomeContent',
            'pageFooter',
        ];
        foreach ($templateVars as $templateVar) {
            $value = (string) $templateMgr->getTemplateVars($templateVar);
            if (str_contains($value, $this->getPlaceholder())) {
                $templateMgr->assign($templateVar, $this->renderLogos($value, $context));
            }
        }

        $templateMgr->assign('currentContext', $context);
    }

    /**
     * Modify the custom navigation menu template variables
     *
     * @see self::renderLogosInTemplates
     */
    protected function modifyCustomNavItem(Context $context, TemplateManager $templateMgr): void
    {
        $content = (string) $templateMgr->getTemplateVars('content');
        if (str_contains($content, $this->getPlaceholder())) {
            $templateMgr->assign('content', $this->renderLogos($content, $context));
        }
    }
}
