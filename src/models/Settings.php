<?php

namespace acalvino4\easyimage\models;

use craft\helpers\ImageTransforms;

/**
 * Easy Image settings
 *
 * @phpstan-type FormatOption 'jpg'|'png'|'gif'|'webp'|'avif'
 */
class Settings extends TransformSet
{
    /** @var TransformSet[] */
    public array $transformSets = [];

    /**
     * @inheritdoc
     *
     * @param mixed $config
     */
    public function __construct(...$config)
    {
        $disallowedOptions = implode(', ', array_diff(array_keys($config), array_merge(static::ALLOWED_CASCADES, ['transformSets'])));
        if ($disallowedOptions) {
            throw new \InvalidArgumentException("Cannot specify the following on Easy Image top-level settings: $disallowedOptions");
        }

        if (!array_key_exists('format', $config)) {
            $config['format'] = 'avif';
        }
        if (!array_key_exists('falbackFormat', $config)) {
            $config['fallbackFormat'] = 'webp';
        }

        parent::__construct(...$config);
    }

    /**
     * Normalizes settings to a format where they can easily be consumed by twig template.
     * This involves cascading settings down to transforms and transform sets,
     * and calculating height and width from aspect ratio if either was left blank.
     */
    public function normalize(): void
    {
        $settingsArr = array_filter($this->toArray());
        foreach ($this->transformSets as &$transformSet) {
            $transformSet->extend($settingsArr);

            $transforms = [];
            foreach ($transformSet->widths as $width) {
                if ($transformSet->aspectRatio) {
                    $height = (int) round($width / $transformSet->aspectRatio);
                }
                $transforms[] = ImageTransforms::extendTransform($transformSet, [
                    'width' => $width,
                    'height' => $height ?? 0,
                ]);
            }
            $transformSet->transforms = $transforms;
        }
    }

    /**
     * Image transforms are often used in sets via the picture tag for responsive image loading.
     * This paramater takes an array where each element's key is the name of the transform set (e.g. 'hero', 'product-thumbnail')
     * The value is a list of the Image Transforms to be generated for the set.
     * Typically you'll only need to set width and height, but all settings (https://craftcms.com/docs/4.x/image-transforms.html#defining-transforms-from-the-control-panel) are supported, other than format, which is determined by top level settings.
     *
     * @param TransformSet[] $transformSets The array of TransformSet.
     * @return self
     */
    public function transformSets(array $transformSets): self
    {
        $this->transformSets = $transformSets;
        return $this;
    }

    /**
     * Sets the primary format to which images should be transformed. Defaults to avif.
     *
     * @param FormatOption $format
     * @return self
     */
    public function format(string $format): self
    {
        $this->format = $format;
        return $this;
    }

    /**
     * Sets the fallback format to which images should be transformed. Defaults to webp.
     *
     * @param FormatOption $fallbackFormat
     * @return self
     */
    public function fallbackFormat(string $fallbackFormat): self
    {
        $this->fallbackFormat = $fallbackFormat;
        return $this;
    }
}
