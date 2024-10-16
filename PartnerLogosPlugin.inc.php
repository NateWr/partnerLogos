<?php

use Illuminate\Support\Collection;

import('lib.pkp.classes.plugins.GenericPlugin');

class PartnerLogosPlugin extends GenericPlugin
{

	public const LIBRARY_FILE_TYPE_PARTNER = 0x00101;

  public function getDisplayName() {
		return __('plugins.generic.partnerLogos.displayName');
	}

	public function getDescription() {
		return __('plugins.generic.partnerLogos.description');
	}

	public function register($category, $path, $mainContextId = null)
	{
		if (!parent::register($category, $path, $mainContextId)) {
      return false;
    }
		HookRegistry::register('PublisherLibrary::types::names', [$this, 'addFileTypeName']);
		HookRegistry::register('PublisherLibrary::types::titles', [$this, 'addFileTypeTitle']);
		HookRegistry::register('PublisherLibrary::types::suffixes', [$this, 'addFileTypeSuffix']);
		HookRegistry::register('TemplateManager::display', [$this, 'addTemplateVariable']);
		return true;
	}

	public function addFileTypeName(string $hookName, array $args): bool
	{
		$names = &$args[0];
		$names[self::LIBRARY_FILE_TYPE_PARTNER] = 'partners';
		return false;
	}

	public function addFileTypeTitle(string $hookName, array $args): bool
	{
		$names = &$args[0];
		$names[self::LIBRARY_FILE_TYPE_PARTNER] = 'plugins.generic.partnerLogos.fileType';
		return false;
	}

	public function addFileTypeSuffix(string $hookName, array $args): bool
	{
		$names = &$args[0];
		$names[self::LIBRARY_FILE_TYPE_PARTNER] = 'PAR';
		return false;
	}

	public function addTemplateVariable(string $hookName, array $args): bool
	{
		$context = Application::get()->getRequest()->getContext();
		if (!$context) {
			return false;
		}
		$logos = $this->getFiles($context->getId());
		$templateMgr = $args[0];
		$templateMgr->assign('partnerLogos', $this->getHtml($logos, $context));
		return false;
	}

	public function getFiles(int $contextId): Collection
	{
		/* @var $libraryFileDao LibraryFileDAO */
		$libraryFileDao = DAORegistry::getDAO('LibraryFileDAO');
		return collect(
			$libraryFileDao->getByContextId($contextId, self::LIBRARY_FILE_TYPE_PARTNER)->toArray()
		);
	}

	public function getHtml(Collection $files, Context $context): string
	{
		return $files
			->map(function(LibraryFile $file) use ($context) {
				return Application::get()
					->getRequest()
					->url($context->getPath(), 'libraryFiles', 'downloadPublic', $file->getId());
			})
			->map(fn(string $url) => "<img src='{$url}'>")
			->join('');
	}
}
