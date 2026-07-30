<?php
declare(strict_types=1);

namespace LaboratorioDigital;

final class ProductImageService
{
    /** @param array<string, mixed> $config */
    public function __construct(
        private readonly string $projectRoot,
        private readonly array $config
    ) {
    }

    /**
     * @param array<string, mixed> $upload Elemento de $_FILES.
     * @return array{image_path: string, size_bytes: int, mime_type: string}
     */
    public function receive(array $upload): array
    {
        $file = $this->validateUpload($upload);
        $relativeDirectory = date('Y/m');
        $directory = $this->storageRoot() . '/' . $relativeDirectory;

        if (
            !is_dir($directory)
            && !mkdir($directory, 0755, true)
            && !is_dir($directory)
        ) {
            throw new \RuntimeException(
                'No se pudo crear la carpeta de fotos de productos.'
            );
        }

        $filename = bin2hex(random_bytes(24)) . '.' . $file['extension'];
        $absolutePath = $directory . '/' . $filename;
        if (!move_uploaded_file($file['tmp_name'], $absolutePath)) {
            throw new \RuntimeException('No se pudo guardar la foto del producto.');
        }
        @chmod($absolutePath, 0644);

        return [
            'image_path' => $this->publicRoot()
                . '/'
                . $relativeDirectory
                . '/'
                . $filename,
            'size_bytes' => $file['size_bytes'],
            'mime_type' => $file['mime_type'],
        ];
    }

    /**
     * @param array<string, mixed> $upload
     * @return array{tmp_name: string, mime_type: string, size_bytes: int, extension: string}
     */
    private function validateUpload(array $upload): array
    {
        if ((int) ($upload['error'] ?? UPLOAD_ERR_NO_FILE) !== UPLOAD_ERR_OK) {
            throw new ValidationException('No se pudo recibir la foto.');
        }

        $tmpName = (string) ($upload['tmp_name'] ?? '');
        $size = (int) ($upload['size'] ?? 0);
        $maxBytes = 8 * 1024 * 1024;

        if ($tmpName === '' || !is_uploaded_file($tmpName)) {
            throw new ValidationException('La carga de la foto no es válida.');
        }
        if ($size < 1 || $size > $maxBytes) {
            throw new ValidationException('La foto supera el límite de 8 MB.');
        }

        $finfo = new \finfo(FILEINFO_MIME_TYPE);
        $mimeType = (string) $finfo->file($tmpName);
        $extensions = [
            'image/jpeg' => 'jpg',
            'image/png' => 'png',
            'image/webp' => 'webp',
        ];
        if (!isset($extensions[$mimeType])) {
            throw new ValidationException(
                'La foto debe ser un archivo JPG, PNG o WebP.'
            );
        }

        $dimensions = @getimagesize($tmpName);
        if (
            $dimensions === false
            || (int) ($dimensions[0] ?? 0) < 1
            || (int) ($dimensions[1] ?? 0) < 1
            || (int) $dimensions[0] > 10000
            || (int) $dimensions[1] > 10000
        ) {
            throw new ValidationException('La imagen no es válida o es demasiado grande.');
        }

        return [
            'tmp_name' => $tmpName,
            'mime_type' => $mimeType,
            'size_bytes' => $size,
            'extension' => $extensions[$mimeType],
        ];
    }

    private function storageRoot(): string
    {
        return rtrim(
            $this->projectRoot . '/v1/uploads/products',
            '/\\'
        );
    }

    private function publicRoot(): string
    {
        $storePath = '/' . trim(
            (string) ($this->config['public_store_path'] ?? '/v1'),
            '/'
        );
        if ($storePath === '/') {
            $storePath = '';
        }

        return $storePath . '/uploads/products';
    }
}
