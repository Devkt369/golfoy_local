<?php
declare(strict_types=1);

namespace ArtM\ProductSlider\Plugin\Cms;

class TemplateFilterPlugin
{
    public function beforeFilter(\Magento\Cms\Model\Template\Filter $subject, string $value): array
    {
        return [$this->stripKnownInvalidTemplate($this->replaceDirectives($value))];
    }

    public function aroundFilter(\Magento\Cms\Model\Template\Filter $subject, callable $proceed, string $value): string
    {
        $prepared = $this->stripKnownInvalidTemplate($this->replaceDirectives($value));

        try {
            return (string)$proceed($prepared);
        } catch (\Throwable $exception) {
            $message = (string)$exception->getMessage();
            if (stripos($message, 'invalid template file') === false
                || stripos($message, 'slider_test.phtml') === false
            ) {
                throw $exception;
            }

            $sanitized = $this->stripKnownInvalidTemplate($prepared);
            return (string)$proceed($sanitized);
        }
    }

    /**
     * Guard block directives against known invalid template paths that can break CMS rendering.
     *
     * @param array<int, mixed> $construction
     */
    public function aroundBlockDirective(
        \Magento\Cms\Model\Template\Filter $subject,
        callable $proceed,
        array $construction
    ): string {
        try {
            return (string)$proceed($construction);
        } catch (\Throwable $exception) {
            $message = (string)$exception->getMessage();
            if (stripos($message, 'invalid template file') === false
                || stripos($message, 'slider_test.phtml') === false
            ) {
                throw $exception;
            }

            $safeConstruction = $construction;
            if (isset($safeConstruction[2]) && is_string($safeConstruction[2])) {
                $safeConstruction[2] = $this->stripKnownInvalidTemplate($safeConstruction[2]);
            }

            return (string)$proceed($safeConstruction);
        }
    }

    private function replaceDirectives(string $content): string
    {
        return (string)preg_replace_callback(
            '/\{\{\s*(product_slider|product_slider_group)\s+([^}]*)\}\}/i',
            function (array $matches): string {
                $directive = strtolower((string)($matches[1] ?? ''));
                $attrs = $this->parseAttributes((string)($matches[2] ?? ''));

                if ($directive === 'product_slider') {
                    return $this->buildSliderBlockDirective($attrs, (string)$matches[0]);
                }

                if ($directive === 'product_slider_group') {
                    return $this->buildGroupBlockDirective($attrs, (string)$matches[0]);
                }

                return (string)$matches[0];
            },
            $content
        );
    }

    private function parseAttributes(string $raw): array
    {
        $attrs = [];
        if (preg_match_all('/([a-zA-Z0-9_]+)\s*=\s*(?:"([^"]*)"|\'([^\']*)\')/', $raw, $matches, PREG_SET_ORDER)) {
            foreach ($matches as $match) {
                $key = strtolower(trim((string)$match[1]));
                $value = isset($match[2]) && $match[2] !== '' ? (string)$match[2] : (string)($match[3] ?? '');
                $attrs[$key] = trim($value);
            }
        }

        return $attrs;
    }

    private function buildSliderBlockDirective(array $attrs, string $fallback): string
    {
        if (!empty($attrs['id']) && ctype_digit((string)$attrs['id'])) {
            return '{{block class="ArtM\\ProductSlider\\Block\\Slider" slider_id="' . (int)$attrs['id'] . '" template="ArtM_ProductSlider::slider/view.phtml"}}';
        }

        $identifier = trim((string)($attrs['identifier'] ?? ''));
        if ($identifier !== '') {
            return '{{block class="ArtM\\ProductSlider\\Block\\Slider" identifier="' . $this->escapeDirective($identifier) . '" template="ArtM_ProductSlider::slider/view.phtml"}}';
        }

        return $fallback;
    }

    private function buildGroupBlockDirective(array $attrs, string $fallback): string
    {
        if (!empty($attrs['id']) && ctype_digit((string)$attrs['id'])) {
            return '{{block class="ArtM\\ProductSlider\\Block\\Group" group_id="' . (int)$attrs['id'] . '" template="ArtM_ProductSlider::group/view.phtml"}}';
        }

        $identifier = trim((string)($attrs['identifier'] ?? ''));
        if ($identifier !== '') {
            return '{{block class="ArtM\\ProductSlider\\Block\\Group" identifier="' . $this->escapeDirective($identifier) . '" template="ArtM_ProductSlider::group/view.phtml"}}';
        }

        return $fallback;
    }

    private function escapeDirective(string $value): string
    {
        return str_replace('"', '\\"', $value);
    }

    private function stripKnownInvalidTemplate(string $content): string
    {
        $patterns = [
            '/\s+template\s*=\s*"Magento_Theme::html\/test\/slider_test\.phtml"/i',
            '/\s+template\s*=\s*\'Magento_Theme::html\/test\/slider_test\.phtml\'/i',
        ];

        return (string)preg_replace($patterns, '', $content);
    }
}
