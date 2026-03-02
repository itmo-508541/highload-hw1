<?php

declare(strict_types=1);

namespace App\Service;

use App\Traits\SluggerTrait;
use Symfony\Component\HttpFoundation\File\Exception\FileException;
use Symfony\Component\HttpFoundation\File\UploadedFile;

class FileUploader
{
    use SluggerTrait;

    public function __construct(private string $targetDirectory)
    {
    }

    public function upload(UploadedFile $file): ?string
    {
        $originalFilename = pathinfo($file->getClientOriginalName(), \PATHINFO_FILENAME);
        $safeFilename = $this->slugger->slug($originalFilename);
        $fileName = $safeFilename.'-'.uniqid().'.'.$file->guessExtension();

        try {
            $file->move($this->getTargetDirectory(), $fileName);
        } catch (FileException) {
            $fileName = null;
        }

        return $fileName;
    }

    public function delete(string $filename): void
    {
        $path = sprintf('%s/%s', $this->getTargetDirectory(), $filename);
        if (file_exists($path) && is_file($path) && is_writable($path)) {
            @unlink($path);
        }
    }

    public function getTargetDirectory(): string
    {
        return $this->targetDirectory;
    }
}
