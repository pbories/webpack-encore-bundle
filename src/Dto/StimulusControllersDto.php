<?php

declare(strict_types=1);

/*
 * This file is part of the Symfony WebpackEncoreBundle package.
 * (c) Fabien Potencier <fabien@symfony.com>
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Symfony\WebpackEncoreBundle\Dto;

use Twig\Markup;

final class StimulusControllersDto extends AbstractStimulusDto
{
    private $controllers = [];
    private $values = [];
    private $outlets = [];
    private $classes = [];

    public function addController(string $controllerName, array $controllerValues = [], array $controllerClasses = []): void
    {
        $controllerName = $this->getFormattedControllerName($controllerName);
        $this->controllers[] = $controllerName;

        foreach ($controllerValues as $key => $value) {
            if (null === $value) {
                continue;
            }

            $key = $this->escapeAsHtmlAttr($this->normalizeKeyName($key));
            $value = $this->getFormattedValue($value);

            $this->values['data-'.$controllerName.'-'.$key.'-value'] = $value;
        }

        foreach ($controllerClasses as $key => $class) {
            $key = $this->escapeAsHtmlAttr($this->normalizeKeyName($key));

            $this->classes['data-'.$controllerName.'-'.$key.'-class'] = $class;
        }
    }

    public function addOutlet(string $outletName, string $selector)
    {
        if (1 < \count($this->controllers)) {
            throw new \LengthException('You cannot call addOutlet() method when passing more than one controller identifier to stimulus_controller() function');
        }

        $this->outlets['data-'.$this->controllers[0].'-'.$outletName.'-outlet'] = $selector;

        return new Markup($this, 'UTF-8');
    }

    public function __toString(): string
    {
        if (0 === \count($this->controllers)) {
            return '';
        }

        return rtrim(
            'data-controller="'.implode(' ', $this->controllers).'" '.
            implode(' ', array_map(function (string $attribute, string $value): string {
                return $attribute.'="'.$this->escapeAsHtmlAttr($value).'"';
            }, array_keys($this->values), $this->values)).' '.
            implode(' ', array_map(function (string $attribute, string $value): string {
                return $attribute.'="'.$this->escapeAsHtmlAttr($value).'"';
            }, array_keys($this->classes), $this->classes)).' '.
            implode(' ', array_map(function (string $attribute, string $value): string {
                return $attribute.'="'.$this->escapeAsHtmlAttr($value).'"';
            }, array_keys($this->outlets), $this->outlets))
        );
    }

    public function toArray(): array
    {
        if (0 === \count($this->controllers)) {
            return [];
        }

        return [
            'data-controller' => implode(' ', $this->controllers),
        ] + $this->values + $this->classes;
    }

    /**
     * Normalize a Stimulus Value API key into its HTML equivalent ("kebab case").
     * Backport features from symfony/string.
     *
     * @see https://stimulus.hotwired.dev/reference/values
     */
    private function normalizeKeyName(string $str): string
    {
        // Adapted from ByteString::camel
        $str = ucfirst(str_replace(' ', '', ucwords(preg_replace('/[^a-zA-Z0-9\x7f-\xff]++/', ' ', $str))));

        // Adapted from ByteString::snake
        return strtolower(preg_replace(['/([A-Z]+)([A-Z][a-z])/', '/([a-z\d])([A-Z])/'], '\1-\2', $str));
    }
}
