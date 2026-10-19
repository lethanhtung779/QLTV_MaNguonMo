<?php
declare(strict_types=1);

namespace Library\Services;

/**
 * Barcode & QR Code SVG Generator for Paper Haven
 * Generates standards-compliant pure SVG barcodes (Code 128) and real scannable QR Code matrices (ISO/IEC 18004).
 * Zero external dependencies, secure, high quality vector output for print and high-res screen display.
 */
final class BarcodeService
{
    /**
     * Code 128 patterns for characters 0-106
     */
    private const CODE128_PATTERNS = [
        '212222', '222122', '222221', '121223', '121322', '131222', '122213', '122312', '132212', '221213',
        '221312', '231212', '112232', '122132', '122231', '113222', '123122', '123221', '223211', '221132',
        '221231', '213212', '223112', '312131', '311222', '321122', '321221', '312212', '322112', '322211',
        '212123', '212321', '232121', '111323', '131123', '131321', '112313', '132113', '132311', '211313',
        '231113', '231311', '112133', '112331', '132131', '113123', '113321', '133121', '313121', '211331',
        '231131', '213113', '213311', '213131', '311123', '311321', '331121', '312113', '312311', '332111',
        '314111', '221411', '431111', '111224', '111422', '121124', '121421', '141122', '141221', '112214',
        '112412', '122114', '122411', '142112', '142211', '241211', '221114', '413111', '241112', '134111',
        '111242', '121142', '121241', '114212', '124112', '124211', '411212', '421112', '421211', '212141',
        '214121', '412121', '111143', '111341', '131141', '114113', '114311', '411113', '411311', '113141',
        '114131', '311141', '411131', '211412', '211214', '211232', '2331112'
    ];

    private static array $gfExp = [];
    private static array $gfLog = [];

    /**
     * Render Code 128 barcode as pure vector SVG
     */
    public function renderBarcodeSvg(string $text, int $height = 68, int $scale = 2): string
    {
        $text = trim($text);
        if ($text === '' || strlen($text) > 80 || preg_match('/[^\x20-\x7E]/', $text)) {
            throw new \InvalidArgumentException('Mã vạch Code 128 phải dài 1–80 ký tự ASCII in được.');
        }

        // Use Subset C for numeric runs to keep printed labels scannable, and
        // Subset B for the remaining printable ASCII characters.
        $len = strlen($text);
        $numericRun = static function (string $value, int $offset): int {
            $count = 0;
            $length = strlen($value);
            while ($offset + $count < $length && ctype_digit($value[$offset + $count])) $count++;
            return $count;
        };
        $subset = $numericRun($text, 0) >= 4 ? 'C' : 'B';
        $values = [$subset === 'C' ? 105 : 104];

        for ($i = 0; $i < $len;) {
            if ($subset === 'C') {
                if ($i + 1 < $len && ctype_digit($text[$i]) && ctype_digit($text[$i + 1])) {
                    $values[] = (int) substr($text, $i, 2);
                    $i += 2;
                    continue;
                }
                $values[] = 100; // Code B
                $subset = 'B';
                continue;
            }

            $run = $numericRun($text, $i);
            if ($run >= 4) {
                if ($run % 2 === 1) {
                    $values[] = ord($text[$i]) - 32;
                    $i++;
                }
                $values[] = 99; // Code C
                $subset = 'C';
                continue;
            }
            $values[] = ord($text[$i]) - 32;
            $i++;
        }

        $checksum = $values[0];
        for ($i = 1, $count = count($values); $i < $count; $i++) {
            $checksum += $values[$i] * $i;
        }
        $checksum %= 103;
        $values[] = $checksum;
        $values[] = 106; // Stop character

        // Convert values to bar patterns
        $bars = '';
        foreach ($values as $val) {
            $pattern = self::CODE128_PATTERNS[$val] ?? self::CODE128_PATTERNS[0];
            $bars .= $pattern;
        }

        $totalModules = 0;
        $patternLen = strlen($bars);
        for ($i = 0; $i < $patternLen; $i++) {
            $totalModules += (int) $bars[$i];
        }

        $width = ($totalModules + 24) * $scale;
        $svgHeight = $height + 42;

        $svg = '<svg xmlns="http://www.w3.org/2000/svg" width="' . $width . '" height="' . $svgHeight . '" viewBox="0 0 ' . $width . ' ' . $svgHeight . '">';
        // Pure crisp background without border lines encroaching on quiet zone
        $svg .= '<rect width="100%" height="100%" fill="#FFFFFF"/>';

        // Header label
        $svg .= '<text x="' . ($width / 2) . '" y="16" font-family="Inter, -apple-system, sans-serif" font-size="9" font-weight="700" fill="#847C70" letter-spacing="1.5" text-anchor="middle">PAPER HAVEN LIBRARY</text>';

        // Bars
        $x = 12 * $scale;
        $barY = 24;
        $isBar = true;
        for ($i = 0; $i < $patternLen; $i++) {
            $w = ((int) $bars[$i]) * $scale;
            if ($isBar) {
                $svg .= '<rect x="' . $x . '" y="' . $barY . '" width="' . $w . '" height="' . $height . '" fill="#111827"/>';
            }
            $x += $w;
            $isBar = !$isBar;
        }

        // Human readable text
        $svg .= '<text x="' . ($width / 2) . '" y="' . ($barY + $height + 15) . '" font-family="Consolas, Monaco, monospace" font-size="12" font-weight="700" fill="#193E30" letter-spacing="1" text-anchor="middle">' . htmlspecialchars($text, ENT_QUOTES, 'UTF-8') . '</text>';
        $svg .= '</svg>';

        return $svg;
    }

    /**
     * Render a standards-compliant ISO/IEC 18004 QR Code as pure vector SVG.
     * Scannable by any mobile camera or 2D barcode scanner.
     */
    public function renderQrSvg(string $payload, int $size = 220): string
    {
        $payload = trim($payload);
        if ($payload === '' || strlen($payload) > 62) {
            throw new \InvalidArgumentException('Mã QR hỗ trợ 1–62 byte UTF-8; không được cắt dữ liệu.');
        }

        $qr = $this->generateQrMatrix($payload);
        $matrix = $qr['matrix'];
        $matrixSize = $qr['size'];

        // Add 4 modules quiet zone padding
        $padding = 4;
        $totalGrid = $matrixSize + ($padding * 2);
        $cellSize = max(4, (int) floor($size / $totalGrid));
        $actualSvgSize = $totalGrid * $cellSize;
        $extraHeight = 28; // for caption label
        $totalHeight = $actualSvgSize + $extraHeight;

        $svg = '<svg xmlns="http://www.w3.org/2000/svg" width="' . $actualSvgSize . '" height="' . $totalHeight . '" viewBox="0 0 ' . $actualSvgSize . ' ' . $totalHeight . '">';
        // Clean card background without edge strokes encroaching on quiet zone
        $svg .= '<rect width="100%" height="100%" fill="#FFFFFF"/>';

        // Draw QR modules
        for ($y = 0; $y < $matrixSize; $y++) {
            for ($x = 0; $x < $matrixSize; $x++) {
                if ($matrix[$y][$x] === 1) {
                    $px = ($x + $padding) * $cellSize;
                    $py = ($y + $padding) * $cellSize;
                    $svg .= '<rect x="' . $px . '" y="' . $py . '" width="' . $cellSize . '" height="' . $cellSize . '" fill="#111827"/>';
                }
            }
        }

        // Caption label below QR
        $svg .= '<text x="' . ($actualSvgSize / 2) . '" y="' . ($actualSvgSize + 19) . '" font-family="Consolas, Monaco, monospace" font-size="11" font-weight="700" fill="#193E30" text-anchor="middle">' . htmlspecialchars($payload, ENT_QUOTES, 'UTF-8') . '</text>';
        $svg .= '</svg>';

        return $svg;
    }

    /**
     * ISO/IEC 18004 QR Matrix Generator (Version 1-M through 4-M).
     */
    private function generateQrMatrix(string $text): array
    {
        $this->initGf();
        $len = strlen($text);

        if ($len <= 14) {
            [$version, $dataCodewords, $blockCount, $ecPerBlock] = [1, 16, 1, 10];
        } elseif ($len <= 26) {
            [$version, $dataCodewords, $blockCount, $ecPerBlock] = [2, 28, 1, 16];
        } elseif ($len <= 42) {
            [$version, $dataCodewords, $blockCount, $ecPerBlock] = [3, 44, 1, 26];
        } else {
            [$version, $dataCodewords, $blockCount, $ecPerBlock] = [4, 64, 2, 18];
        }
        $size = 17 + (4 * $version);

        // 1. Bitstream: Byte Mode (0100) + 8-bit length
        $bits = '0100';
        $bits .= str_pad(decbin($len), 8, '0', STR_PAD_LEFT);
        for ($i = 0; $i < $len; $i++) {
            $bits .= str_pad(decbin(ord($text[$i])), 8, '0', STR_PAD_LEFT);
        }

        // Terminator (up to 4 zeroes)
        $bits .= '0000';
        if (strlen($bits) > $dataCodewords * 8) {
            throw new \InvalidArgumentException('Dữ liệu QR vượt quá dung lượng của phiên bản đang sử dụng.');
        }

        // Pad to byte boundary
        while (strlen($bits) % 8 !== 0) {
            $bits .= '0';
        }

        // Pad bytes (0xEC, 0x11)
        $padBytes = [0xEC, 0x11];
        $padIdx = 0;
        while (strlen($bits) < $dataCodewords * 8) {
            $bits .= str_pad(decbin($padBytes[$padIdx % 2]), 8, '0', STR_PAD_LEFT);
            $padIdx++;
        }

        // Convert data bits to bytes
        $dataBytes = [];
        for ($i = 0; $i < strlen($bits); $i += 8) {
            $dataBytes[] = bindec(substr($bits, $i, 8));
        }

        // Split into Reed-Solomon blocks, then interleave data and EC codewords.
        $dataPerBlock = intdiv($dataCodewords, $blockCount);
        $dataBlocks = [];
        $ecBlocks = [];
        for ($block = 0; $block < $blockCount; $block++) {
            $dataBlocks[$block] = array_slice($dataBytes, $block * $dataPerBlock, $dataPerBlock);
            $ecBlocks[$block] = $this->rsComputeRemainder($dataBlocks[$block], $ecPerBlock);
        }
        $allCodewords = [];
        for ($i = 0; $i < $dataPerBlock; $i++) {
            for ($block = 0; $block < $blockCount; $block++) {
                $allCodewords[] = $dataBlocks[$block][$i];
            }
        }
        for ($i = 0; $i < $ecPerBlock; $i++) {
            for ($block = 0; $block < $blockCount; $block++) {
                $allCodewords[] = $ecBlocks[$block][$i];
            }
        }

        // Convert all codewords to bitstring
        $finalBitstream = '';
        foreach ($allCodewords as $cw) {
            $finalBitstream .= str_pad(decbin($cw), 8, '0', STR_PAD_LEFT);
        }

        // Initialize matrix (-1 = unset)
        $matrix = array_fill(0, $size, array_fill(0, $size, -1));

        // Place 3 Finder Patterns
        $setFinder = function(int $r0, int $c0) use (&$matrix) {
            for ($r = -1; $r <= 7; $r++) {
                for ($c = -1; $c <= 7; $c++) {
                    $row = $r0 + $r;
                    $col = $c0 + $c;
                    if ($row < 0 || $row >= count($matrix) || $col < 0 || $col >= count($matrix)) continue;
                    if ($r === -1 || $r === 7 || $c === -1 || $c === 7) {
                        $matrix[$row][$col] = 0; // 1-module separator
                    } elseif ($r === 0 || $r === 6 || $c === 0 || $c === 6) {
                        $matrix[$row][$col] = 1;
                    } elseif ($r >= 2 && $r <= 4 && $c >= 2 && $c <= 4) {
                        $matrix[$row][$col] = 1;
                    } else {
                        $matrix[$row][$col] = 0;
                    }
                }
            }
        };

        $setFinder(0, 0);
        $setFinder(0, $size - 7);
        $setFinder($size - 7, 0);

        // Versions 2 through 4 have one alignment pattern.
        if ($version >= 2) {
            $ar = $size - 7; $ac = $size - 7;
            for ($r = -2; $r <= 2; $r++) {
                for ($c = -2; $c <= 2; $c++) {
                    if (abs($r) === 2 || abs($c) === 2 || ($r === 0 && $c === 0)) {
                        $matrix[$ar + $r][$ac + $c] = 1;
                    } else {
                        $matrix[$ar + $r][$ac + $c] = 0;
                    }
                }
            }
        }

        // Timing patterns
        for ($i = 8; $i < $size - 8; $i++) {
            if ($matrix[6][$i] === -1) $matrix[6][$i] = ($i % 2 === 0) ? 1 : 0;
            if ($matrix[$i][6] === -1) $matrix[$i][6] = ($i % 2 === 0) ? 1 : 0;
        }

        // Dark module
        $matrix[4 * $version + 9][8] = 1;

        // Reserve Format Info positions
        for ($i = 0; $i < 9; $i++) {
            if ($matrix[8][$i] === -1) $matrix[8][$i] = 0;
            if ($matrix[$i][8] === -1) $matrix[$i][8] = 0;
        }
        for ($i = $size - 8; $i < $size; $i++) {
            if ($matrix[8][$i] === -1) $matrix[8][$i] = 0;
        }
        for ($i = $size - 7; $i < $size; $i++) {
            if ($matrix[$i][8] === -1) $matrix[$i][8] = 0;
        }

        // Place Data Codewords (zig-zag from right to left)
        $bitIdx = 0;
        $totalBits = strlen($finalBitstream);
        $goingUp = true;

        for ($col = $size - 1; $col > 0; $col -= 2) {
            if ($col === 6) $col--; // Skip vertical timing column

            $rows = $goingUp ? range($size - 1, 0) : range(0, $size - 1);
            foreach ($rows as $row) {
                foreach ([$col, $col - 1] as $c) {
                    if ($matrix[$row][$c] === -1) {
                        $bit = ($bitIdx < $totalBits) ? (int)$finalBitstream[$bitIdx] : 0;
                        // Mask Pattern 0: (row + col) % 2 === 0
                        if (($row + $c) % 2 === 0) {
                            $bit ^= 1;
                        }
                        $matrix[$row][$c] = $bit;
                        $bitIdx++;
                    }
                }
            }
            $goingUp = !$goingUp;
        }

        // Level M (00), Mask 0 (000) format word is 0x5412. ISO/IEC
        // 18004 places it least-significant bit first in both locations.
        $format = 0x5412;
        for ($i = 0; $i < 15; $i++) {
            $bit = ($format >> $i) & 1;

            if ($i < 6) {
                $matrix[$i][8] = $bit;
            } elseif ($i < 8) {
                $matrix[$i + 1][8] = $bit;
            } elseif ($i === 8) {
                $matrix[8][7] = $bit;
            } else {
                $matrix[8][14 - $i] = $bit;
            }

            if ($i < 8) {
                $matrix[8][$size - 1 - $i] = $bit;
            } else {
                $matrix[$size - 15 + $i][8] = $bit;
            }
        }
        $matrix[$size - 8][8] = 1;

        return [
            'version' => $version,
            'size' => $size,
            'matrix' => $matrix
        ];
    }

    private function initGf(): void
    {
        if (!empty(self::$gfExp)) return;
        self::$gfExp = array_fill(0, 512, 0);
        self::$gfLog = array_fill(0, 256, 0);
        $x = 1;
        for ($i = 0; $i < 255; $i++) {
            self::$gfExp[$i] = $x;
            self::$gfLog[$x] = $i;
            $x <<= 1;
            if ($x & 0x100) {
                $x ^= 0x11D;
            }
        }
        for ($i = 255; $i < 512; $i++) {
            self::$gfExp[$i] = self::$gfExp[$i - 255];
        }
    }

    private function gfMul(int $x, int $y): int
    {
        if ($x === 0 || $y === 0) return 0;
        return self::$gfExp[self::$gfLog[$x] + self::$gfLog[$y]];
    }

    private function rsGeneratorPoly(int $degree): array
    {
        $poly = [1];
        for ($i = 0; $i < $degree; $i++) {
            $next = [1, self::$gfExp[$i]];
            $res = array_fill(0, count($poly) + 1, 0);
            for ($j = 0; $j < count($poly); $j++) {
                $res[$j] ^= $poly[$j];
                $res[$j + 1] ^= $this->gfMul($poly[$j], $next[1]);
            }
            $poly = $res;
        }
        return $poly;
    }

    private function rsComputeRemainder(array $data, int $ecCount): array
    {
        $gen = $this->rsGeneratorPoly($ecCount);
        $rem = array_merge($data, array_fill(0, $ecCount, 0));
        $dataLen = count($data);

        for ($i = 0; $i < $dataLen; $i++) {
            $factor = $rem[$i];
            if ($factor !== 0) {
                for ($j = 0; $j < count($gen); $j++) {
                    $rem[$i + $j] ^= $this->gfMul($gen[$j], $factor);
                }
            }
        }

        return array_slice($rem, $dataLen);
    }
}
