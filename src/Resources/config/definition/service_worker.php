<?php

declare(strict_types=1);

use Symfony\Component\Config\Definition\Configurator\DefinitionConfigurator;

return static function (DefinitionConfigurator $definition): void {
    $definition->rootNode()
        ->children()
            ->arrayNode('serviceworker')
                ->canBeEnabled()
                ->beforeNormalization()
                ->ifString()
                ->then(static fn (string $v): array => [
                    'enabled' => true,
                    'src' => $v,
                ])
            ->end()
            ->children()
                ->scalarNode('src')
                    ->isRequired()
                    ->info('The path to the service worker source file. Can be served by Asset Mapper.')
                    ->example('script/sw.js')
                ->end()
                ->scalarNode('dest')
                    ->cannotBeEmpty()
                    ->defaultValue('/sw.js')
                    ->info('The public URL to the service worker.')
                    ->example('/sw.js')
                ->end()
                ->booleanNode('skip_waiting')
                    ->defaultFalse()
                    ->info('Whether to skip waiting for the service worker to be activated.')
                ->end()
                ->scalarNode('scope')
                    ->cannotBeEmpty()
                    ->defaultValue('/')
                    ->info('The scope of the service worker.')
                    ->example('/app/')
                ->end()
                ->booleanNode('use_cache')
                    ->defaultTrue()
                    ->info('Whether the service worker should use the cache.')
                ->end()
                ->arrayNode('workbox')
                    ->info('The configuration of the workbox.')
                    ->canBeDisabled()
                    ->beforeNormalization()
                        ->ifTrue(static fn (mixed $v): bool => true)
                        ->then(static function (mixed $v): array {
                            if (isset($v['asset_cache'])) {
                                return $v;
                            }
                            $v['asset_cache'] = array_filter([
                                'enabled' => true,
                                'cache_name' => $v['asset_cache_name'] ?? 'assets',
                                'regex' => $v['static_regex'] ?? '/\.(css|js|json|xml|txt|map|ico|png|jpe?g|gif|svg|webp|bmp)$/',
                            ], static fn (mixed $v): bool => $v !== null);

                            return $v;
                        })
                    ->end()
                    ->beforeNormalization()
                        ->ifTrue(static fn (mixed $v): bool => true)
                        ->then(static function (mixed $v): array {
                            if (isset($v['image_cache'])) {
                                return $v;
                            }
                            $v['image_cache'] = array_filter([
                                'enabled' => true,
                                'cache_name' => $v['image_cache_name'] ?? 'images',
                                'regex' => $v['image_regex'] ?? '/\.(ico|png|jpe?g|gif|svg|webp|bmp)$/',
                                'max_entries' => $v['max_image_cache_entries'] ?? 60,
                                'max_age' => $v['max_image_age'] ?? 60 * 60 * 24 * 365,
                            ], static fn (mixed $v): bool => $v !== null);

                            return $v;
                        })
                    ->end()
                    ->beforeNormalization()
                        ->ifTrue(static fn (mixed $v): bool => true)
                        ->then(static function (mixed $v): array {
                            if (isset($v['font_cache'])) {
                                return $v;
                            }
                            $v['font_cache'] = array_filter([
                                'enabled' => true,
                                'cache_name' => $v['font_cache_name'] ?? 'fonts',
                                'regex' => $v['font_regex'] ?? '/\.(ttf|eot|otf|woff2)$/',
                                'max_entries' => $v['max_font_cache_entries'] ?? 60,
                                'max_age' => $v['max_font_age'] ?? 60 * 60 * 24 * 365,
                            ], static fn (mixed $v): bool => $v !== null);

                            return $v;
                        })
                    ->end()
                    ->beforeNormalization()
                        ->ifTrue(static fn (mixed $v): bool => true)
                        ->then(static function (mixed $v): array {
                            if (isset($v['page_cache'])) {
                                return $v;
                            }
                            $v['page_cache'] = array_filter([
                                'enabled' => true,
                                'cache_name' => $v['page_cache_name'] ?? 'pages',
                                'network_timeout' => $v['network_timeout_seconds'] ?? 3,
                                'urls' => $v['warm_cache_urls'] ?? [],
                            ], static fn (mixed $v): bool => $v !== null);

                            return $v;
                        })
                    ->end()
                    ->beforeNormalization()
                        ->ifTrue(static fn (mixed $v): bool => true)
                        ->then(static function (mixed $v): array {
                            if (isset($v['offline_fallback'])) {
                                return $v;
                            }
                            $v['offline_fallback'] = array_filter([
                                'enabled' => true,
                                'page' => $v['page_fallback'] ?? null,
                                'image' => $v['image_fallback'] ?? null,
                                'font' => $v['font_fallback'] ?? null,
                            ], static fn (mixed $v): bool => $v !== null);

                            return $v;
                        })
                    ->end()
                    ->children()
                        ->booleanNode('use_cdn')
                            ->defaultFalse()
                            ->info('Whether to use the local workbox or the CDN.')
                        ->end()
                        ->arrayNode('google_fonts')
                            ->canBeDisabled()
                            ->children()
                                ->scalarNode('cache_prefix')
                                    ->defaultNull()
                                    ->info('The cache prefix for the Google fonts.')
                                ->end()
                                ->integerNode('max_age')
                                    ->defaultNull()
                                    ->info('The maximum age of the Google fonts cache (in seconds).')
                                ->end()
                                ->integerNode('max_entries')
                                    ->defaultNull()
                                    ->info('The maximum number of entries in the Google fonts cache.')
                                ->end()
                            ->end()
                        ->end()
                        ->booleanNode('cache_manifest')
                            ->defaultTrue()
                            ->info('Whether to cache the manifest file.')
                        ->end()
                        ->scalarNode('version')
                            ->defaultValue('7.0.0')
                            ->info('The version of workbox. When using local files, the version shall be "7.0.0."')
                        ->end()
                        ->scalarNode('workbox_public_url')
                            ->defaultValue('/workbox')
                            ->info('The public path to the local workbox. Only used if use_cdn is false.')
                        ->end()
                        ->scalarNode('workbox_import_placeholder')
                            ->setDeprecated(
                                'spomky-labs/phpwa',
                                '1.1.0',
                                'The "%node%" option is deprecated and will be removed in 2.0.0. No replacement.'
                            )
                            ->defaultValue('//WORKBOX_IMPORT_PLACEHOLDER')
                            ->info('The placeholder for the workbox import. Will be replaced by the workbox import.')
                            ->example('//WORKBOX_IMPORT_PLACEHOLDER')
                        ->end()
                        ->scalarNode('standard_rules_placeholder')
                            ->setDeprecated(
                                'spomky-labs/phpwa',
                                '1.1.0',
                                'The "%node%" option is deprecated and will be removed in 2.0.0. No replacement.'
                            )
                            ->defaultValue('//STANDARD_RULES_PLACEHOLDER')
                            ->info('The placeholder for the standard rules. Will be replaced by caching strategies.')
                            ->example('//STANDARD_RULES_PLACEHOLDER')
                        ->end()
                        ->scalarNode('offline_fallback_placeholder')
                            ->setDeprecated(
                                'spomky-labs/phpwa',
                                '1.1.0',
                                'The "%node%" option is deprecated and will be removed in 2.0.0. No replacement.'
                            )
                            ->defaultValue('//OFFLINE_FALLBACK_PLACEHOLDER')
                            ->info('The placeholder for the offline fallback. Will be replaced by the URL.')
                            ->example('//OFFLINE_FALLBACK_PLACEHOLDER')
                        ->end()
                        ->scalarNode('widgets_placeholder')
                            ->setDeprecated(
                                'spomky-labs/phpwa',
                                '1.1.0',
                                'The "%node%" option is deprecated and will be removed in 2.0.0. No replacement.'
                            )
                            ->defaultValue('//WIDGETS_PLACEHOLDER')
                            ->info(
                                'The placeholder for the widgets. Will be replaced by the widgets management events.'
                            )
                            ->example('//WIDGETS_PLACEHOLDER')
                        ->end()
                        ->booleanNode('clear_cache')
                            ->defaultTrue()
                            ->info('Whether to clear the cache during the service worker activation.')
                        ->end()
                        ->arrayNode('offline_fallback')
                            ->canBeDisabled()
                            ->children()
                                ->append(getUrlNode('page', 'The URL of the offline page fallback.'))
                                ->append(getUrlNode('image', 'The URL of the offline image fallback.'))
                                ->append(getUrlNode('font', 'The URL of the offline font fallback.'))
                            ->end()
                        ->end()
                        ->arrayNode('image_cache')
                            ->canBeDisabled()
                            ->children()
                                ->scalarNode('cache_name')
                                    ->defaultValue('images')
                                    ->info('The name of the image cache.')
                                ->end()
                                ->scalarNode('regex')
                                    ->defaultValue('/\.(ico|png|jpe?g|gif|svg|webp|bmp)$/')
                                    ->info('The regex to match the images.')
                                    ->example('/\.(ico|png|jpe?g|gif|svg|webp|bmp)$/')
                                ->end()
                                ->integerNode('max_entries')
                                    ->defaultValue(60)
                                    ->info('The maximum number of entries in the image cache.')
                                    ->example([50, 100, 200])
                                ->end()
                                ->integerNode('max_age')
                                    ->defaultValue(60 * 60 * 24 * 365)
                                    ->info('The maximum number of seconds before the image cache is invalidated.')
                                    ->example([60 * 60 * 24 * 365, 60 * 60 * 24 * 30, 60 * 60 * 24 * 7])
                                ->end()
                            ->end()
                        ->end()
                        ->arrayNode('asset_cache')
                            ->canBeDisabled()
                            ->children()
                                ->scalarNode('cache_name')
                                    ->defaultValue('assets')
                                    ->info('The name of the asset cache.')
                                ->end()
                                ->scalarNode('regex')
                                    ->defaultValue('/\.(css|js|json|xml|txt|map|ico|png|jpe?g|gif|svg|webp|bmp)$/')
                                    ->info('The regex to match the assets.')
                                    ->example('/\.(css|js|json|xml|txt|map|ico|png|jpe?g|gif|svg|webp|bmp)$/')
                                ->end()
                            ->end()
                        ->end()
                        ->arrayNode('font_cache')
                            ->canBeDisabled()
                            ->children()
                                ->scalarNode('cache_name')
                                    ->defaultValue('fonts')
                                    ->info('The name of the font cache.')
                                ->end()
                                ->scalarNode('regex')
                                    ->defaultValue('/\.(ttf|eot|otf|woff2)$/')
                                    ->info('The regex to match the fonts.')
                                    ->example('/\.(ttf|eot|otf|woff2)$/')
                                ->end()
                                ->integerNode('max_entries')
                                    ->defaultValue(60)
                                    ->info('The maximum number of entries in the image cache.')
                                    ->example([50, 100, 200])
                                ->end()
                                ->integerNode('max_age')
                                    ->defaultValue(60 * 60 * 24 * 365)
                                    ->info('The maximum number of seconds before the font cache is invalidated.')
                                    ->example([60 * 60 * 24 * 365, 60 * 60 * 24 * 30, 60 * 60 * 24 * 7])
                                ->end()
                            ->end()
                        ->end()
                        ->arrayNode('page_cache')
                            ->canBeDisabled()
                            ->children()
                                ->scalarNode('cache_name')
                                    ->defaultValue('pages')
                                    ->info('The name of the page cache.')
                                ->end()
                                ->integerNode('network_timeout')
                                    ->defaultValue(3)
                                    ->info(
                                        'The network timeout in seconds before cache is called (for warm cache URLs only).'
                                    )
                                    ->example([1, 2, 5])
                                ->end()
                                ->arrayNode('urls')
                                    ->treatNullLike([])
                                    ->treatFalseLike([])
                                    ->treatTrueLike([])
                                    ->info('The URLs to warm the cache. The URLs shall be served by the application.')
                                    ->arrayPrototype()
                                        ->beforeNormalization()
                                            ->ifString()
                                            ->then(static fn (string $v): array => [
                                                'path' => $v,
                                            ])
                                        ->end()
                                        ->children()
                                            ->scalarNode('path')
                                                ->isRequired()
                                                ->info('The URL of the shortcut.')
                                                ->example('app_homepage')
                                            ->end()
                                            ->arrayNode('params')
                                                ->treatFalseLike([])
                                                ->treatTrueLike([])
                                                ->treatNullLike([])
                                                ->prototype('variable')->end()
                                                ->info('The parameters of the action.')
                                            ->end()
                                        ->end()
                                    ->end()
                                ->end()
                            ->end()
                        ->end()
                        ->arrayNode('background_sync')
                            ->treatNullLike([])
                            ->treatFalseLike([])
                            ->treatTrueLike([])
                            ->info('The background sync configuration.')
                            ->arrayPrototype()
                                ->children()
                                    ->scalarNode('queue_name')
                                        ->isRequired()
                                        ->info('The name of the queue.')
                                        ->example(['api-requests', 'image-uploads'])
                                    ->end()
                                    ->scalarNode('regex')
                                        ->isRequired()
                                        ->info('The regex to match the URLs.')
                                        ->example(['/\/api\//'])
                                    ->end()
                                    ->scalarNode('method')
                                        ->defaultValue('POST')
                                        ->info('The HTTP method.')
                                        ->example(['POST', 'PUT', 'PATCH', 'DELETE'])
                                    ->end()
                                    ->integerNode('max_retention_time')
                                        ->defaultValue(60 * 24 * 5)
                                        ->info('The maximum retention time in minutes.')
                                    ->end()
                                    ->booleanNode('force_sync_callback')
                                        ->defaultFalse()
                                        ->info('Whether to force the sync callback.')
                                    ->end()
                                ->end()
                            ->end()
                        ->end()
                        ->scalarNode('image_cache_name')
                            ->defaultValue('images')
                            ->info('The name of the image cache.')
                            ->setDeprecated(
                                'spomky-labs/phpwa',
                                '1.1.0',
                                'The "%node%" option is deprecated and will be removed in 2.0.0. Please use "pwa.serviceworker.workbox.image_cache.cache_name" instead.'
                            )
                        ->end()
                        ->scalarNode('font_cache_name')
                            ->defaultValue('fonts')
                            ->info('The name of the font cache.')
                            ->setDeprecated(
                                'spomky-labs/phpwa',
                                '1.1.0',
                                'The "%node%" option is deprecated and will be removed in 2.0.0. Please use "pwa.serviceworker.workbox.font_cache.cache_name" instead.'
                            )
                        ->end()
                        ->scalarNode('page_cache_name')
                            ->defaultValue('pages')
                            ->info('The name of the page cache.')
                            ->setDeprecated(
                                'spomky-labs/phpwa',
                                '1.1.0',
                                'The "%node%" option is deprecated and will be removed in 2.0.0. Please use "pwa.serviceworker.workbox.page_cache.cache_name" instead.'
                            )
                        ->end()
                        ->scalarNode('asset_cache_name')
                            ->defaultValue('assets')
                            ->info('The name of the asset cache.')
                            ->setDeprecated(
                                'spomky-labs/phpwa',
                                '1.1.0',
                                'The "%node%" option is deprecated and will be removed in 2.0.0. Please use "pwa.serviceworker.workbox.asset_cache.cache_name" instead.'
                            )
                        ->end()
                        ->append(getUrlNode('page_fallback', 'The URL of the offline page fallback.'))
                        ->append(getUrlNode('image_fallback', 'The URL of the offline image fallback.'))
                        ->append(getUrlNode('font_fallback', 'The URL of the offline font fallback.'))
                        ->scalarNode('image_regex')
                            ->defaultValue('/\.(ico|png|jpe?g|gif|svg|webp|bmp)$/')
                            ->info('The regex to match the images.')
                            ->example('/\.(ico|png|jpe?g|gif|svg|webp|bmp)$/')
                            ->setDeprecated(
                                'spomky-labs/phpwa',
                                '1.1.0',
                                'The "%node%" option is deprecated and will be removed in 2.0.0. Please use "pwa.serviceworker.workbox.image_cache.regex" instead.'
                            )
                        ->end()
                        ->scalarNode('static_regex')
                            ->defaultValue('/\.(css|js|json|xml|txt|map)$/')
                            ->info('The regex to match the static files.')
                            ->example('/\.(css|js|json|xml|txt|map)$/')
                            ->setDeprecated(
                                'spomky-labs/phpwa',
                                '1.1.0',
                                'The "%node%" option is deprecated and will be removed in 2.0.0. Please use "pwa.serviceworker.workbox.asset_cache.regex" instead.'
                            )
                        ->end()
                        ->scalarNode('font_regex')
                            ->defaultValue('/\.(ttf|eot|otf|woff2)$/')
                            ->info('The regex to match the static files.')
                            ->example('/\.(ttf|eot|otf|woff2)$/')
                            ->setDeprecated(
                                'spomky-labs/phpwa',
                                '1.1.0',
                                'The "%node%" option is deprecated and will be removed in 2.0.0. Please use "pwa.serviceworker.workbox.font_cache.regex" instead.'
                            )
                        ->end()
                        ->integerNode('max_image_cache_entries')
                            ->defaultValue(60)
                            ->info('The maximum number of entries in the image cache.')
                            ->example([50, 100, 200])
                            ->setDeprecated(
                                'spomky-labs/phpwa',
                                '1.1.0',
                                'The "%node%" option is deprecated and will be removed in 2.0.0. Please use "pwa.serviceworker.workbox.image_cache.max_entries" instead.'
                            )
                        ->end()
                        ->integerNode('max_image_age')
                            ->defaultValue(60 * 60 * 24 * 365)
                            ->info('The maximum number of seconds before the image cache is invalidated.')
                            ->example([60 * 60 * 24 * 365, 60 * 60 * 24 * 30, 60 * 60 * 24 * 7])
                            ->setDeprecated(
                                'spomky-labs/phpwa',
                                '1.1.0',
                                'The "%node%" option is deprecated and will be removed in 2.0.0. Please use "pwa.serviceworker.workbox.image_cache.max_age" instead.'
                            )
                        ->end()
                        ->integerNode('max_font_cache_entries')
                            ->defaultValue(30)
                            ->info('The maximum number of entries in the font cache.')
                            ->example([30, 50, 100])
                            ->setDeprecated(
                                'spomky-labs/phpwa',
                                '1.1.0',
                                'The "%node%" option is deprecated and will be removed in 2.0.0. Please use "pwa.serviceworker.workbox.font_cache.max_entries" instead.'
                            )
                        ->end()
                        ->integerNode('max_font_age')
                            ->defaultValue(60 * 60 * 24 * 365)
                            ->info('The maximum number of seconds before the font cache is invalidated.')
                            ->example([60 * 60 * 24 * 365, 60 * 60 * 24 * 30, 60 * 60 * 24 * 7])
                            ->setDeprecated(
                                'spomky-labs/phpwa',
                                '1.1.0',
                                'The "%node%" option is deprecated and will be removed in 2.0.0. Please use "pwa.serviceworker.workbox.font_cache.max_age" instead.'
                            )
                        ->end()
                        ->integerNode('network_timeout_seconds')
                            ->defaultValue(3)
                            ->info('The network timeout in seconds before cache is called (for warm cache URLs only).')
                            ->example([1, 2, 5])
                            ->setDeprecated(
                                'spomky-labs/phpwa',
                                '1.1.0',
                                'The "%node%" option is deprecated and will be removed in 2.0.0. Please use "pwa.serviceworker.workbox.page_cache.network_timeout" instead.'
                            )
                        ->end()
                        ->arrayNode('warm_cache_urls')
                            ->treatNullLike([])
                            ->treatFalseLike([])
                            ->treatTrueLike([])
                            ->info('The URLs to warm the cache. The URLs shall be served by the application.')
                            ->setDeprecated(
                                'spomky-labs/phpwa',
                                '1.1.0',
                                'The "%node%" option is deprecated and will be removed in 2.0.0. Please use "pwa.serviceworker.workbox.page_cache.urls" instead.'
                            )
                            ->arrayPrototype()
                            ->beforeNormalization()
                            ->ifString()
                                ->then(static fn (string $v): array => [
                                    'path' => $v,
                                ])
                            ->end()
                            ->children()
                                ->scalarNode('path')
                                    ->isRequired()
                                    ->info('The URL of the shortcut.')
                                    ->example('app_homepage')
                                ->end()
                                ->arrayNode('params')
                                    ->treatFalseLike([])
                                    ->treatTrueLike([])
                                    ->treatNullLike([])
                                    ->prototype('variable')->end()
                                    ->info('The parameters of the action.')
                                ->end()
                            ->end()
                        ->end()
                    ->end()
                ->end()
            ->end()
        ->end()
    ->end()
->end()
        ->end();
};