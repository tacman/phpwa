<?php

declare(strict_types=1);

namespace SpomkyLabs\PwaBundle\Subscriber;

use SpomkyLabs\PwaBundle\Dto\Manifest;
use Symfony\Component\AssetMapper\Event\PreAssetsCompileEvent;
use Symfony\Component\AssetMapper\Path\PublicAssetsFilesystemInterface;
use Symfony\Component\DependencyInjection\Attribute\Autowire;
use Symfony\Component\EventDispatcher\Attribute\AsEventListener;
use Symfony\Component\HttpKernel\Config\FileLocator;
use function assert;
use function in_array;
use function is_array;
use function is_string;

#[AsEventListener(PreAssetsCompileEvent::class)]
final readonly class WorkboxCompileEventListener
{
    public function __construct(
        #[Autowire('%spomky_labs_pwa.sw.enabled%')]
        private bool $serviceWorkerEnabled,
        #[Autowire('@asset_mapper.local_public_assets_filesystem')]
        private PublicAssetsFilesystemInterface $assetsFilesystem,
        private Manifest $manifest,
        private FileLocator $fileLocator,
    ) {
    }

    public function __invoke(PreAssetsCompileEvent $event): void
    {
        if (! $this->serviceWorkerEnabled) {
            return;
        }
        $serviceWorker = $this->manifest->serviceWorker;
        if ($serviceWorker === null || $serviceWorker->workbox->enabled !== true || $serviceWorker->workbox->useCDN === true) {
            return;
        }
        $workboxVersion = $serviceWorker->workbox->version;
        $workboxPublicUrl = '/' . trim($serviceWorker->workbox->workboxPublicUrl, '/');

        $resourcePath = $this->fileLocator->locate(
            sprintf('@SpomkyLabsPwaBundle/Resources/workbox-v%s', $workboxVersion)
        );
        if (! is_string($resourcePath)) {
            return;
        }

        $files = scandir($resourcePath);
        assert(is_array($files), 'Unable to list the files.');
        foreach ($files as $file) {
            if (in_array($file, ['.', '..'], true)) {
                continue;
            }
            if (str_contains($file, '.dev.')) {
                continue;
            }
            $content = file_get_contents(sprintf('%s/%s', $resourcePath, $file));
            assert(is_string($content), 'Unable to load the file content.');
            $this->assetsFilesystem->write(sprintf('%s/%s', $workboxPublicUrl, $file), $content);
        }
    }
}
