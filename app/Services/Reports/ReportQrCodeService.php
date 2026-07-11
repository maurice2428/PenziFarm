<?php

namespace App\Services\Reports;

class ReportQrCodeService
{
    public function dataUri(
        string $verificationText,
        int $size = 112
    ): ?string {
        $fromFacade = $this->fromSimpleQrCode(
            $verificationText,
            $size
        );

        if ($fromFacade) {
            return $fromFacade;
        }

        return $this->fromBaconQrCode(
            $verificationText,
            $size
        );
    }

    public function providerAvailable(): bool
    {
        return class_exists(
            \SimpleSoftwareIO\QrCode\Facades\QrCode::class
        ) || (
            class_exists(
                \BaconQrCode\Writer::class
            )
            && class_exists(
                \BaconQrCode\Renderer\ImageRenderer::class
            )
            && class_exists(
                \BaconQrCode\Renderer\RendererStyle\RendererStyle::class
            )
            && class_exists(
                \BaconQrCode\Renderer\Image\SvgImageBackEnd::class
            )
        );
    }

    private function fromSimpleQrCode(
        string $verificationText,
        int $size
    ): ?string {
        if (
            ! class_exists(
                \SimpleSoftwareIO\QrCode\Facades\QrCode::class
            )
        ) {
            return null;
        }

        try {
            $png = \SimpleSoftwareIO\QrCode\Facades\QrCode::format(
                'png'
            )
                ->size($size)
                ->margin(1)
                ->generate($verificationText);

            return 'data:image/png;base64,'
                . base64_encode($png);
        } catch (\Throwable) {
            return null;
        }
    }

    private function fromBaconQrCode(
        string $verificationText,
        int $size
    ): ?string {
        if (
            ! class_exists(
                \BaconQrCode\Writer::class
            )
            || ! class_exists(
                \BaconQrCode\Renderer\ImageRenderer::class
            )
            || ! class_exists(
                \BaconQrCode\Renderer\RendererStyle\RendererStyle::class
            )
            || ! class_exists(
                \BaconQrCode\Renderer\Image\SvgImageBackEnd::class
            )
        ) {
            return null;
        }

        try {
            $renderer = new \BaconQrCode\Renderer\ImageRenderer(
                new \BaconQrCode\Renderer\RendererStyle\RendererStyle(
                    $size,
                    1
                ),
                new \BaconQrCode\Renderer\Image\SvgImageBackEnd()
            );

            $writer = new \BaconQrCode\Writer(
                $renderer
            );

            $svg = $writer->writeString(
                $verificationText
            );

            return 'data:image/svg+xml;base64,'
                . base64_encode($svg);
        } catch (\Throwable) {
            return null;
        }
    }
}
