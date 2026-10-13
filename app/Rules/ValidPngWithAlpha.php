<?php

namespace App\Rules;

use Closure;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Http\UploadedFile;

class ValidPngWithAlpha implements ValidationRule
{
    protected ?int $width = null;
    protected ?int $height = null;

    /**
     * Run the validation rule.
     *
     * @param  \Closure(string, ?string=): \Illuminate\Translation\PotentiallyTranslatedString  $fail
     */
    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        if (!$value instanceof UploadedFile || !$value->isValid()) {
            $fail('File twibbon harus diunggah dengan benar.');
            return;
        }

        // 1. Cek ukuran file (maksimal 2 MB = 2048 KB)
        if ($value->getSize() > 2 * 1024 * 1024) {
            $fail('Ukuran file PNG maksimal 2 MB.');
            return;
        }

        // 2. Cek mime type
        $mime = $value->getMimeType();
        if ($mime !== 'image/png') {
            $fail('Format file harus berupa PNG.');
            return;
        }

        $realPath = $value->getRealPath();
        if (!$realPath || !file_exists($realPath)) {
            $fail('File PNG tidak dapat dibaca.');
            return;
        }

        // 3. Cek signature PNG (8 byte pertama)
        $handle = fopen($realPath, 'rb');
        if (!$handle) {
            $fail('Gagal membuka file PNG.');
            return;
        }

        $signature = fread($handle, 8);
        if ($signature !== "\x89PNG\r\n\x1a\n") {
            fclose($handle);
            $fail('File bukan format PNG yang valid.');
            return;
        }

        // Baca IHDR chunk
        $chunkLength = unpack('N', fread($handle, 4))[1] ?? 0;
        $chunkType = fread($handle, 4);
        if ($chunkType !== 'IHDR' || $chunkLength < 13) {
            fclose($handle);
            $fail('Header PNG tidak valid.');
            return;
        }

        $ihdrData = fread($handle, $chunkLength);
        $crc = fread($handle, 4); // skip crc

        $width = unpack('N', substr($ihdrData, 0, 4))[1] ?? 0;
        $height = unpack('N', substr($ihdrData, 4, 4))[1] ?? 0;
        $colorType = ord($ihdrData[9]);

        $this->width = $width;
        $this->height = $height;

        // Cek chunk tRNS jika color type bukan 4 atau 6
        $hasAlphaChannel = ($colorType === 4 || $colorType === 6);
        $hasTrnsChunk = false;

        while (!feof($handle)) {
            $lenData = fread($handle, 4);
            if (strlen($lenData) < 4) {
                break;
            }
            $len = unpack('N', $lenData)[1];
            $type = fread($handle, 4);
            if ($type === 'tRNS') {
                $hasTrnsChunk = true;
                break;
            }
            if ($type === 'IEND') {
                break;
            }
            // Lewati chunk data + 4 bytes CRC
            fseek($handle, $len + 4, SEEK_CUR);
        }
        fclose($handle);

        if (!$hasAlphaChannel && !$hasTrnsChunk) {
            $fail('File PNG harus memiliki kanal alpha (transparansi).');
            return;
        }

        // 4. Verifikasi keberadaan piksel transparan menggunakan GD
        $im = @imagecreatefrompng($realPath);
        if (!$im) {
            $fail('File PNG rusak atau tidak dapat diproses.');
            return;
        }

        $imgWidth = imagesx($im);
        $imgHeight = imagesy($im);

        $hasTransparentPixel = false;

        // Sampling cepat: periksa titik tengah dan grid 10px
        $stepX = max(1, (int) ($imgWidth / 50));
        $stepY = max(1, (int) ($imgHeight / 50));

        for ($y = 0; $y < $imgHeight; $y += $stepY) {
            for ($x = 0; $x < $imgWidth; $x += $stepX) {
                $rgba = imagecolorat($im, $x, $y);
                $alpha = ($rgba >> 24) & 0x7F; // 0 = opaque, 127 = full transparent
                if ($alpha > 0) {
                    $hasTransparentPixel = true;
                    break 2;
                }
            }
        }

        // Jika sampling tidak menemukan, scan lebih rapat
        if (!$hasTransparentPixel) {
            for ($y = 0; $y < $imgHeight; $y += 2) {
                for ($x = 0; $x < $imgWidth; $x += 2) {
                    $rgba = imagecolorat($im, $x, $y);
                    $alpha = ($rgba >> 24) & 0x7F;
                    if ($alpha > 0) {
                        $hasTransparentPixel = true;
                        break 2;
                    }
                }
            }
        }

        imagedestroy($im);

        if (!$hasTransparentPixel) {
            $fail('File PNG tidak memiliki area transparan untuk foto user.');
        }
    }

    public function getWidth(): ?int
    {
        return $this->width;
    }

    public function getHeight(): ?int
    {
        return $this->height;
    }
}
