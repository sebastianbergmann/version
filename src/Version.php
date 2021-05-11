<?php declare(strict_types=1);
/*
 * This file is part of sebastian/version.
 *
 * (c) Sebastian Bergmann <sebastian@phpunit.de>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */
namespace SebastianBergmann;

use function end;
use function explode;
use function fclose;
use function is_dir;
use function is_resource;
use function proc_close;
use function proc_open;
use function stream_get_contents;
use function substr_count;
use function trim;

final class Version
{
    private string $path;

    private string $release;

    private ?string $version = null;

    public function __construct(string $release, string $path)
    {
        $this->release = $release;
        $this->path    = $path;
    }

    public function getVersion(): string
    {
        if ($this->version === null) {
            if (substr_count($this->release, '.') + 1 === 3) {
                $this->version = $this->release;
            } else {
                $this->version = $this->release . '-dev';
            }

            $git = $this->getGitInformation($this->path);

            if ($git) {
                if (substr_count($this->release, '.') + 1 === 3) {
                    $this->version = $git;
                } else {
                    $git = explode('-', $git);

                    $this->version = $this->release . '-' . end($git);
                }
            }
        }

        return $this->version;
    }

    private function getGitInformation(string $path): bool|string
    {
        if (!is_dir($path . DIRECTORY_SEPARATOR . '.git')) {
            return false;
        }

        $process = proc_open(
            'git describe --tags',
            [
                1 => ['pipe', 'w'],
                2 => ['pipe', 'w'],
            ],
            $pipes,
            $path
        );

        if (!is_resource($process)) {
            return false;
        }

        $result = trim(stream_get_contents($pipes[1]));

        fclose($pipes[1]);
        fclose($pipes[2]);

        $returnCode = proc_close($process);

        if ($returnCode !== 0) {
            return false;
        }

        return $result;
    }
}
