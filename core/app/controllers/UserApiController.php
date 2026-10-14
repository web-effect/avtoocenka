<?php
namespace App\Controllers;
use Zoomx\Controllers;
use App\Services\UserService;

class UserApiController extends \Zoomx\Controllers\Controller
{

    public function update()
    {
        UserService::requireAccess();

        // if (empty($_POST['field']) || !isset($_POST['value'])) {
        //     abortx(400, 'Не переданы данные');
        // }

        // $field = $_POST['field'] ?? '';
        // $value = trim($_POST['value'] ?? '');

        $profile = $this->modx->user->getOne('Profile');
        if (!$profile) abortx(500, 'Профиль не найден');

        $data = array_map('trim', $_POST);

        $profile->set('extended', array_replace(
            $profile->get('extended') ?: [],
            $data
        ));

        // $ext = $profile->get('extended') ?: [];
        // $ext[$field] = $value;
        // $profile->set('extended', $ext);
        $profile->save();

        return jsonx([
            'profile' => UserService::getProfile(),
        ]);
    }

    public function uploadFile()
    {
        zoomx()->autoloadResource(false);
        UserService::requireAccess();

        if (
            empty($_FILES['file']) ||
            $_FILES['file']['error'] !== UPLOAD_ERR_OK
        ) {
            return abortx(400, 'Файл не загружен');
        }

        $name = $_POST['name'] ?? '';
        if (!$name) return abortx(400, 'Не передан name');

        $targetDir = MODX_CORE_PATH . 'uploads/appraisers/' . UserService::id() . '/';

        if (!is_dir($targetDir) && !mkdir($targetDir, 0777, true)) {
            return abortx(500, 'Не удалось создать директорию');
        }

        $extension = strtolower(pathinfo($_FILES['file']['name'], PATHINFO_EXTENSION));

        if (!in_array($extension, ['png', 'jpg', 'jpeg', 'webp'])) {
            return abortx(400, 'Недопустимый формат файла');
        }

        $filename = "{$name}.{$extension}";
        $targetFile = $targetDir . $filename;

        if (!move_uploaded_file($_FILES['file']['tmp_name'], $targetFile)) {
            return abortx(500, 'Не удалось сохранить файл');
        }

        $profile = $this->modx->user->getOne('Profile');
        if (!$profile) abortx(500, 'Профиль не найден');

        $ext = $profile->get('extended') ?: [];
        $ext[$name] = $filename;
        $profile->set('extended', $ext);
        $profile->save();

        return jsonx([
            'profile' => UserService::getProfile(),
        ]);
    }

    public function deleteFile()
    {
        zoomx()->autoloadResource(false);
        UserService::requireAccess();

        $name = $_POST['name'] ?? '';
        if (!$name) return abortx(400, 'Не передан name');

        $dir = MODX_CORE_PATH . 'uploads/appraisers/' . UserService::id() . '/';

        $profile = $this->modx->user->getOne('Profile');
        if (!$profile) abortx(500, 'Профиль не найден');

        $ext = $profile->get('extended') ?: [];

        if (empty($ext[$name])) {
            abortx(400, 'Файл не был загружен');
        }
        if (is_file($dir . $ext[$name])) {
            unlink($dir . $ext[$name]);
        }
        unset($ext[$name]);

        $profile->set('extended', $ext);
        $profile->save();

        return jsonx([
            'profile' => UserService::getProfile(),
        ]);
    }

}