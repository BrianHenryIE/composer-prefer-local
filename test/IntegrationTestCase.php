<?php
/**
 * Creates and deletes a temp directory for tests.
 *
 * Could just system temp directory, but this is useful for setting breakpoints and seeing what has happened.
 */

namespace BrianHenryIE\ComposerPreferLocal;

use Composer\Console\Application as Composer;
use PHPUnit\Framework\TestCase;
use RecursiveDirectoryIterator;
use RecursiveIteratorIterator;
use Symfony\Component\Console\Input\ArgvInput;

/**
 * Class IntegrationTestCase
 * @coversNothing
 */
class IntegrationTestCase extends TestCase
{
	/**
	 * The directory containing all test fixture stuff. Absolute path. Ends with a trailing slash.
	 */
    protected string $testsWorkingDir;

	/**
	 * The directory for the main project composer.json to exist, a subdirectory of $testsWorkingDir called `project`.
	 * Absolute path. Ends with a trailing slash.
	 */
    protected string $projectWorkingDir;

	protected Composer $composer;

    public function setUp(): void
    {
        parent::setUp();

        $this->testsWorkingDir = sys_get_temp_dir()
            . '/prefer-local/';

        if ('Darwin' === PHP_OS) {
            $this->testsWorkingDir = '/private' . $this->testsWorkingDir;
        }

        if (file_exists($this->testsWorkingDir)) {
            $this->deleteDir($this->testsWorkingDir);
        }

	    $this->composer = new Composer();
	    $this->composer->setAutoExit(false);

        @mkdir($this->testsWorkingDir);
        @mkdir($this->testsWorkingDir . 'project');

		$this->projectWorkingDir = $this->testsWorkingDir . 'project/';
    }


    protected function runComposer(string $command) {
        try {
            return $this->composer->run(new ArgvInput(explode(' ', $command)));
        }catch( \Exception $exception ) {
            self::fail( $exception->getMessage());
        }
    }


    /**
     * Delete $this->testsWorkingDir after each test.
     *
     * @see https://stackoverflow.com/questions/3349753/delete-directory-with-files-in-it
     */
    public function tearDown(): void
    {
        parent::tearDown();

        $dir = $this->testsWorkingDir;

        $this->deleteDir($dir);
    }

    protected function deleteDir($dir)
    {
        if (!file_exists($dir)) {
            return;
        }

        $it = new RecursiveDirectoryIterator($dir, RecursiveDirectoryIterator::SKIP_DOTS);
        $files = new RecursiveIteratorIterator($it, RecursiveIteratorIterator::CHILD_FIRST);
        foreach ($files as $file) {
            if (is_link($file)) {
                unlink($file);
            } elseif ($file->isDir()) {
                rmdir($file->getRealPath());
            } else {
                unlink($file->getRealPath());
            }
        }
        rmdir($dir);
    }
}
