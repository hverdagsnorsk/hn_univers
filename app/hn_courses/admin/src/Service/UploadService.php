<?php
namespace HnCourses\Admin\Service;

class UploadService
{
    public function upload(array $file): string
    {
        if ($file['error'] !== UPLOAD_ERR_OK) {
            throw new \RuntimeException('Upload feilet');
        }

        $ext = pathinfo($file['name'], PATHINFO_EXTENSION);
        $name = uniqid('doc_', true) . '.' . $ext;

        $target = HN_UPLOAD_PATH . $name;

        if (!move_uploaded_file($file['tmp_name'], $target)) {
            throw new \RuntimeException('Kunne ikke lagre fil');
        }

        return $name;
    }
}
