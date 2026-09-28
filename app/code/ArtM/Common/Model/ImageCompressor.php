<?php
namespace ArtM\Common\Model;

class ImageCompressor
{
    /**
     * If enabled and file is an image, compress it in place to reduce file size.
     *
     * @param string $absolutePath Absolute path to the image file
     */
    public function maybeCompressImage(string $absolutePath): void
    {
        if (!is_file($absolutePath) || !is_readable($absolutePath) || !is_writable($absolutePath)) {
            return;
        }

        $info = @getimagesize($absolutePath);
        if ($info === false || empty($info['mime'])) {
            return;
        }

        $mime = $info['mime'];

        switch ($mime) {
            case 'image/jpeg':
                $this->compressJpeg($absolutePath, 78);
                break;
            case 'image/png':
                $this->compressPng($absolutePath, 6);
                break;
            case 'image/webp':
                $this->compressWebp($absolutePath, 78);
                break;
            default:
                return; // unsupported types untouched
        }
    }

    /**
     * Compress JPEG image with given quality.
     */
    private function compressJpeg(string $absolutePath, int $quality): void
    {
        $img = @imagecreatefromjpeg($absolutePath);
        if ($img === false) {
            return;
        }

        @imagejpeg($img, $absolutePath, max(10, min(100, $quality)));
        imagedestroy($img);
    }

    /**
     * Compress PNG image with given compression level (0–9).
     */
    private function compressPng(string $absolutePath, int $level): void
    {
        $img = @imagecreatefrompng($absolutePath);
        if ($img === false) {
            return;
        }

        @imagepng($img, $absolutePath, max(0, min(9, $level)));
        imagedestroy($img);
    }

    /**
     * Compress WEBP image with given quality.
     */
    private function compressWebp(string $absolutePath, int $quality): void
    {
        if (!function_exists('imagecreatefromwebp') || !function_exists('imagewebp')) {
            return;
        }

        $img = @imagecreatefromwebp($absolutePath);
        if ($img === false) {
            return;
        }

        @imagewebp($img, $absolutePath, max(10, min(100, $quality)));
        imagedestroy($img);
    }
}
