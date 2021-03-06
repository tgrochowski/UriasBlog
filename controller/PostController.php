<?php

//TODO :: add geo-localisation, tags
/**
 * Class AdminController Manages admin pages & user logic
 */
class PostController extends BaseController
{
    public $size;
    public $sizeBytes;

    public function __construct()
    {
        parent::__construct();
        $this->size = ini_get('post_max_size');
        $this->sizeBytes = $this->return_bytes($this->size);
    }

    public function createAction(){
        $this->checkPermission();

        if(isset($_POST["submit"])){
            $title = $_POST["title"];
            $tags = $_POST["tags"];
            $description = $_POST["description"] ?? "";
            $thumbnail = $_POST["thumbnail"];
            $rotationArray = $_POST["rotation"] ?? [];

            $userId = $_SESSION["user"]["id"];
            $this->db->insert("post",[
                "date" => date("Y-m-d H:i:s"),
                "tags" => $tags,
                "description" => $description,
                "title" => $title,
                "author_id" => $userId,
                "location" => "[x,y]"
            ]);
            $postId = $this->db->getLastInsertId();

            $this->uploadImages($title, $rotationArray, $thumbnail, $postId);
        }

        $this->vc->assign('tags',$this->getAllTags());
        $this->vc->assign('maxSize',$this->size);
        $this->vc->assign('maxSizeBytes',$this->sizeBytes);
        $this->vc->assign('maxCount',ini_get('max_file_uploads'));
        $this->vc->renderAll("newPost","admin");
    }

    /**
     * @param $params [id]
     */
    public function updateAction($params){
        $this->checkPermission();
        extract($params);
        $post = $this->db->fetchOne("post",["id" => $id]);
        $images = $this->db->fetch("media",["post_id" => $id]);

        $oldTitle = $post["title"];
        $dateArr = explode(" ",$post["date"]);
        $date = reset($dateArr);

        if(isset($_POST["submit"])) {
            $title = $_POST["title"];

            $tags = $_POST["tags"];
            $description = $_POST["description"] ?? "";
            $thumbnail = $_POST["thumbnail"];
            $rotationArray = $_POST["rotation"] ?? [];
            $deleteArray = $_POST["delete"] ?? [];

            $this->removeImages($deleteArray);

            $this->db->update("post",[
                "tags" => $tags,
                "description" => $description,
                "title" => $title,
                "location" => "[x,y]"
            ],$id);

            if(strcmp($title,$oldTitle) != 0){
                var_dump("$title");
                var_dump("$oldTitle");
                //title has changed, rename folder
                $newTitle = $this->urlize($title,"_");
                rename ("uploads/$date/$oldTitle/","uploads/$date/$newTitle/");
            }

            $this->uploadImages($title, $rotationArray, $thumbnail, $id);
            $this->redirect("admin/post/edit/$id");
        }

        $size = ini_get('post_max_size');
        $sizeBytes = $this->return_bytes($size);

        $title = str_replace(" ","_",$post["title"]);

        $this->vc->assign('tags',$this->getAllTags());
        $this->vc->assign('imgFolder',"/uploads/$date/$title/");
        $this->vc->assign('maxSize',$size);
        $this->vc->assign('maxSizeBytes',$sizeBytes);
        $this->vc->assign('maxCount',ini_get('max_file_uploads'));
        $this->vc->assign('post',$post);
        $this->vc->assign('images',$images);
        $this->vc->renderAll("updatePost","admin");
    }

    /**
     * @param $params [id]
     */
    public function deleteAction(array $params){
        $this->checkPermission();
        extract($params);
        $post = $this->db->fetchOne("post",["id" => $id],"date, title");
        $title = $post["title"];
        $title = str_replace(" ","_",$title);
        $dateParts = explode(" ",$post["date"]);
        $date = reset($dateParts);
        $this->deleteDir("uploads/$date/$title");
        $this->db->delete("post",["id" => $id]);
        $this->redirect("admin");
    }

    protected function return_bytes(string $val) : int{
        $val = trim($val);
        $last = strtolower($val[strlen($val)-1]);
        $ret = 1;
        switch($last) {
            case 'g':
                $ret *= 1024;
            case 'm':
                $ret *= 1024;
            case 'k':
                $ret *= 1024;
        }
        return $ret;
    }

    protected function reArrayFiles(&$file_post) : array {
        $file_ary = array();
        $file_count = count($file_post["images"]["name"]);
        $file_keys = array_keys($file_post["images"]);
        for ($i=0; $i<$file_count; $i++) {
            foreach ($file_keys as $key) {
                $file_ary[$i][$key] = $file_post["images"][$key][$i];
            }
        }
        return $file_ary;
    }

    /**
     * Image rotation and quality change
     */
    private function imgPostUpload(string $src,int $degrees = null,int $quality) {
        $system = explode(".", $src);
        if (preg_match("/jpg|jpeg/", $system[1]))
            $src_img=imagecreatefromjpeg($src);
        if (preg_match("/png/", $system[1]))
            $src_img = imagecreatefrompng($src);
        if (preg_match("/gif/", $system[1]))
            $src_img = imagecreatefromgif($src);

        if($degrees!=null){
            $degrees = - $degrees;
        }else{
            $degrees = 0;
        }

        $rotate = imagerotate($src_img, $degrees,$quality);

        if (preg_match("/png/", $system[1]))
            imagepng($rotate,$src);
        else if (preg_match("/gif/", $system[1]))
            imagegif($rotate, $src);
        else
            imagejpeg($rotate, $src);

        imagedestroy($rotate);
        imagedestroy($src_img);
    }

    public function deleteDir($dirPath) {
        if (! is_dir($dirPath)) {
            throw new InvalidArgumentException("$dirPath must be a directory");
        }
        if (substr($dirPath, strlen($dirPath) - 1, 1) != '/') {
            $dirPath .= '/';
        }
        $files = glob($dirPath . '*', GLOB_MARK);
        foreach ($files as $file) {
            if (is_dir($file)) {
                $this->deleteDir($file);
            } else {
                unlink($file);
            }
        }
        rmdir($dirPath);
    }

    protected function getAllTags() : string{
        $posts = $this->db->fetch('post',null,"tags");
        $allTags = [];
        foreach ($posts as $post) {
            $postTags = $post["tags"];
            if(!empty($postTags)) {
                $postTagsArray = explode(",", $postTags);
                $allTags = array_merge($allTags, $postTagsArray);
            }
        }
        return '["' . implode('", "', array_unique($allTags)) . '"]';
    }

    protected function uploadImages(string $title,array $rotationArray,string $thumbnail, $postId)
    {
        if (isset($_FILES["images"])) {
            $sentSize = array_sum($_FILES["images"]["size"]);
            if ($sentSize <= $this->sizeBytes && $sentSize > 0) {

                //create dir
                $directory = 'uploads/' . date("Y-m-d") . "/" . $this->urlize($title,"_") . "/";
                if (!file_exists($directory)) {
                    mkdir($directory, 0777, true);
                }

                foreach ($this->reArrayFiles($_FILES) as $key => $file) {
                    //get mime type
                    $finfo = new finfo(FILEINFO_MIME_TYPE);
                    if (false === $ext = array_search(
                            $finfo->file($file['tmp_name']),
                            array(
                                'jpeg' => 'image/jpeg',
                                'png' => 'image/png',
                                'gif' => 'image/gif',
                            ),
                            true
                        )
                    )
                        throw new RuntimeException('Invalid file format.');

                    //upload
                    $hashed = sha1_file($file['tmp_name']);
                    if (!move_uploaded_file(
                        $file['tmp_name'],
                        sprintf($directory . '%s.%s',
                            $hashed,
                            $ext
                        )
                    )
                    )
                        throw new RuntimeException('Failed to move uploaded file.');

                    //rotate and cut
                    $source = $directory . $hashed . '.' . $ext;
                    if (array_key_exists($file["name"], $rotationArray)) {
                        $degrees = $rotationArray[$file["name"]];
                        $this->imgPostUpload($source, $degrees, 80);
                    } else {
                        $this->imgPostUpload($source, null, 80);
                    }

                    //save hashed thumnail name
                    if ($file["name"] == $thumbnail || ( $thumbnail == null && $key == 0)) {
                        $thumbnail = $hashedThumbnail = $hashed . '.' . $ext;
                        $this->db->update("post", ["thumbnail" => $hashedThumbnail], $postId);
                    }

                    $this->db->insert("media", [
                        "filename" => $hashed,
                        "extension" => $ext,
                        "originalName" => $file["name"],
                        "post_id" => $postId,
                        "type" => 'image',
                    ]);
                }
            } else {
                $this->vc->assign('error', $this->size);
            }
        }
    }


    private function removeImages(array $ids)
    {
        foreach ($ids as $name => $id)
            $this->db->delete("media",["id" => $id]);
    }
}