<?php
/**
 * When a package is found in a neighbouring directory, use it instead of the Packagist version.
 */

namespace BrianHenryIE\ComposerPreferLocal;

use Composer\Composer;
use Composer\IO\IOInterface;
use Composer\Plugin\PluginInterface;
use Symfony\Component\Filesystem\Filesystem;

class PreferLocalPackagesPlugin implements PluginInterface
{
	public function activate(Composer $composer, IOInterface $io)
	{
		$composerRequires = array_merge(
			$composer->getPackage()->getRequires(),
			$composer->getPackage()->getDevRequires()
		);
		unset($composerRequires['brianhenryie/composer-prefer-local']);

		$filesystem = new Filesystem();

		$parentDirectory = realpath(getcwd() . '/../') . '/';

		$localPackageDirs = $this->getLocalPackageDirs($parentDirectory, $filesystem);

		foreach( $localPackageDirs as $absolutePath => $packageName ) {

			// TODO: Compare with published versions, so only communicate when the local version is different.

			$repository = $composer->getRepositoryManager()->createRepository(
				'path',
				[
					"type" => "path",
					"url" => $absolutePath,
				]
			);
			$composer->getRepositoryManager()->prependRepository($repository);

			// TODO: should this be shown outside install and update invocations?
			$io->write("Using {$absolutePath} for {$packageName}.");
		}
	}

	/**
	 * array < absolutePath, packageName >
	 */
	protected function getLocalPackageDirs(string $parentDirectory, Filesystem $filesystem): array
	{
		$localPackageDirs = array_filter(
			array_filter(
				array_map(
					function($dir) use ($parentDirectory) {
						return $parentDirectory . $dir . '/';
					},
					array_filter(
						array_merge(
							scandir(getcwd()),
							scandir($parentDirectory),
						),
						function ($dir) {
							return $dir !== '.' && $dir !== '..' && $dir !== basename( getcwd() );
						}
					)
				),
				'is_dir'
			),
			function($dir) use ($filesystem, $parentDirectory) {
				return $filesystem->exists( $dir . 'composer.json');
			}
		);

		foreach($localPackageDirs as $index => $localPackageDir) {
			unset($localPackageDirs[$index]);
			$packageComposer = json_decode(file_get_contents($localPackageDir . 'composer.json'), JSON_THROW_ON_ERROR);
			if(!isset($packageComposer['name'])){
				// How does this happen?
				// A `composer.json` without a `name` key?
				continue;
			}
			$localPackageDirs[$localPackageDir] = $packageComposer['name'];
		}

		return $localPackageDirs;
	}

	public function deactivate(Composer $composer, IOInterface $io)
	{
		// TODO: Implement deactivate() method.
	}

	public function uninstall(Composer $composer, IOInterface $io)
	{
		// TODO: Implement uninstall() method.
	}
}
