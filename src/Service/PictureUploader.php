<?php

namespace App\Service;

use Symfony\Component\HttpFoundation\File\Exception\FileException;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use Symfony\Component\String\Slugger\SluggerInterface;

class PictureUploader
{

    public function __construct(private SluggerInterface $slugger)
    {
    }

    public function upload(UploadedFile $file, string $path)
    {
        $originalFilename = pathinfo($file->getClientOriginalName(), PATHINFO_FILENAME);
        $safeFilename = $this->slugger->slug($originalFilename);
        $fileName = $safeFilename . '-' . uniqid() . '.' . $file->guessExtension();

        try {
            $file->move($path, $fileName);
        } catch (FileException $e) {
            return $e->getMessage();
        }

        return $path . $fileName;
    }
}
