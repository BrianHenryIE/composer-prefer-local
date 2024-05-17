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
			if(!isset($packageComposer['name'])){
				// How does this happen?
				continue;
			}
			$packageComposer = json_decode(file_get_contents($localPackageDir . 'composer.json'), JSON_THROW_ON_ERROR);
			$localPackageDirs[$localPackageDir] = $packageComposer['name'];
			unset($localPackageDirs[$index]);
		}

	    foreach( $composerRequires as $packageName => $composerRequire ) {

			if( !in_array($packageName, $localPackageDirs, true) ){
				continue;
			}

			$absolutePath = array_search($packageName, $localPackageDirs, true);

	        $repository = $composer->getRepositoryManager()->createRepository(
		        'path',
					[
						"type" => "path",
						"url" => $absolutePath,
		            ]
	        );
			$composer->getRepositoryManager()->addRepository($repository);

	        $io->write("Using {$absolutePath} for {$packageName}.");
        }
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
