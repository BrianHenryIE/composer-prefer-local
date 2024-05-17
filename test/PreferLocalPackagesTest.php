<?php
/**
 * @see \BrianHenryIE\ComposerPreferLocal\PreferLocalPackagesPlugin
 */

namespace BrianHenryIE\ComposerPreferLocal;

use Composer\IO\NullIO;

class PreferLocalPackagesTest extends IntegrationTestCase {

	public function setUp(): void {
		parent::setUp();

		$projectDirectory = realpath(getcwd().'/..');

		$composerJsonString = <<<EOD
{
  "name": "brianhenryie/composer-prefer-local-packages-test",
  "repositories": [
	{
	  "type": "path",
	  "url": "$projectDirectory"
	}
  ],
  "config": {
    "secure-http": false
  },
  "minimum-stability": "dev",
  "prefer-stable": true
}
EOD;
		json_decode($composerJsonString,JSON_THROW_ON_ERROR);

		file_put_contents($this->projectWorkingDir . 'composer.json', $composerJsonString);

		chdir($this->projectWorkingDir);

		$this->runComposer("composer install");
		$this->runComposer("composer config allow-plugins.brianhenryie/composer-prefer-local true");
		$this->runComposer("composer require brianhenryie/composer-prefer-local:dev-main --dev");
	}

	public function test_it_works(): void {

		$composerJsonString = <<<EOD
{
    "name": "brianhenryie/a-local-project",
    "require": {
        "psr/log": "*"
    }
}
EOD;

		$packageDir = $this->testsWorkingDir . 'my-local-copy/';
		@mkdir($packageDir);
		file_put_contents($packageDir . 'composer.json', $composerJsonString);

		$this->runComposer('composer require brianhenryie/a-local-project:*');

		$sut = new PreferLocalPackagesPlugin();

		$sut->activate($this->composer->getComposer(), new NullIO());

		self::assertDirectoryExists($this->projectWorkingDir . 'vendor/psr/log');
	}
}