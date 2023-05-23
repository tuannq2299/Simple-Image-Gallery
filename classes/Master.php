<?php
require_once('../config.php');
Class Master extends DBConnection {
	private $settings;
	public function __construct(){
		global $_settings;
		$this->settings = $_settings;
		parent::__construct();
	}
	public function __destruct(){
		parent::__destruct();
	}
	function capture_err(){
		if(!$this->conn->error)
			return false;
		else{
			$resp['status'] = 'failed';
			$resp['error'] = $this->conn->error;
			if(isset($sql))
			$resp['sql'] = $sql;
			return json_encode($resp);
			exit;
		}
	}
	function save_album(){
		extract($_POST);
		$data = "";
		foreach($_POST as $k=> $v){
			if($k != 'id'){
				if(!empty($data)) $data.=", ";
				$data.=" {$k} = '{$v}'";
			}
		}
		$check = $this->conn->query("SELECT * FROM `album_list` where `name` = '{$name}' ".(!empty($id) ? "and id != {$id}" : ''))->num_rows;
		$this->capture_err();
		if($check > 0){
			$resp['status'] = 'failed';
			$resp['msg'] = "Album Already Exists.";
		}else{
			if(empty($id)){
				$sql = "INSERT INTO `album_list` set $data";
				$save = $this->conn->query($sql);
			}else{
				$sql = "UPDATE `album_list` set $data where id = {$id}";
				$save = $this->conn->query($sql);
			}
			$this->capture_err();

			if($save){
				$resp['status'] = "success";
				$this->settings->set_flashdata('success'," Album Successfully Saved");
			}else{
				$resp['status'] = "failed";
				$resp['sql'] = $sql;
			}
		}
		return json_encode($resp);
	}

	function delete_album(){
		$sql = "UPDATE `album_list` set delete_f = 1 where id = '{$_POST['id']}' ";
		$delete = $this->conn->query($sql);
		$this->capture_err();
		if($delete){
			$resp['status'] = 'success';
			$img_arch = $this->conn->query("UPDATE `images` set delete_f = 1 where album_id = '{$_POST['id']}' ");
			$this->settings->set_flashdata('success'," Album Successfully Deleted. The data will be saved at archives temporarily");
		}else{
			$resp['status'] = "failed";
			$resp['sql'] = $sql;
		}
		return json_encode($resp);
	}
	function retrieve_album(){
		$sql = "UPDATE `album_list` set delete_f = 0 where id = '{$_POST['id']}' ";
		$retrieve = $this->conn->query($sql);
		$this->capture_err();
		if($retrieve){
			$sql = "UPDATE `images` set delete_f = 0 where album_id = '{$_POST['id']}' ";
			$retrieve_imgs = $this->conn->query($sql);
			if($retrieve_imgs){
				$this->settings->set_flashdata('success'," Album Successfully Retrieved.");
				$resp['status'] = 'success';
			}else{
				$resp['status'] = 'failed';
				$resp['msg'] = 'Album Successfully Retrieved but images are not';
				$resp['error'] = $this->conn->error;
				$resp['sql'] = $sql;
			}
		}else{
			$resp['status'] = "failed";
			$resp['sql'] = $sql;
		}
		return json_encode($resp);
	}

	function save_image(){
		extract($_POST);
		if(isset($_FILES['images']['tmp_name']) && count($_FILES['images']['tmp_name'])>0){
			$data = "";
			if(!is_dir(base_app.'uploads/user_'.$user_id))
				mkdir(base_app.'uploads/user_'.$user_id);
			if(!is_dir(base_app.'uploads/user_'.$user_id.'/album_'.$album_id))
				mkdir(base_app.'uploads/user_'.$user_id.'/album_'.$album_id);
			$path = 'uploads/user_'.$user_id.'/album_'.$album_id.'/';
			for($i = 0; $i < count($_FILES['images']['tmp_name']); $i++){
				if(!empty($_FILES['images']['tmp_name'])){
					$oname = $_FILES['images']['name'][$i];
					$ext = pathinfo($oname, PATHINFO_EXTENSION);
					if(!is_file(base_app.$path.(strtotime(date('Y-m-d H:i'))).'.'.$ext))
						$nname = $path.(strtotime(date('Y-m-d H:i'))).'.'.$ext;
					else{
						$o = 0;
						while(true){
							$o++;
							$nname = $path.(strtotime(date('Y-m-d H:i'))).'_'.$o.'.'.$ext;
							if(!is_file(base_app.$path.(strtotime(date('Y-m-d H:i'))).'_'.$o.'.'.$ext))
								break;
						}
					}
					$move = move_uploaded_file($_FILES['images']['tmp_name'][$i],base_app.$nname);
					if($move){
						if(!empty($data)) $data .=",";
						$data .= "('{$album_id}','{$oname}','{$nname}','{$user_id}')";
					}
				}
			}
			if(!empty($data)){
				$sql = "INSERT INTO `images` (`album_id`,`original_name`,`path_name`,`user_id`) VALUES {$data}";
				$save = $this->conn->query($sql);
				if($data){
					$resp['status'] = 'success';
					$this->settings->set_flashdata('success',' Image/s successfully uploaded');
				}else{
					$resp['status'] = 'failded';
					$resp['error'] = $this->conn->error;
					$resp['sql'] = $sql;
				}
			}else{
				$resp['status'] = 'failed';
				$resp['msg'] = "Error Uploading image/s.";
			}

		}else{
			$resp['status'] = 'failed';
			$resp['msg'] = "Select atleast 1 image first.";
		}
		return json_encode($resp);
	}
	function rename_image(){
		extract($_POST);
		$nname = $original_name.$ext;
		$sql = "UPDATE `images` set `original_name` = '{$nname}' where id = '{$id}' ";
		$save = $this->conn->query($sql);
		if($save){
			$resp['status'] = 'success';
			$this->settings->set_flashdata('success',' Image successfully renamed');
		}else{
			$this->settings->set_flashdata('success',' Image successfully renamed');
			$resp['status'] = 'failed';
			$resp['msg'] = "Error Renaming image.";
			$resp['error'] = $this->conn->error;
			$resp['sql'] = $sql;
		}
		return json_encode($resp);
	}
	function delete_image(){
		$sql = "UPDATE `images` set delete_f = 1 where id = '{$_POST['id']}' ";
		$delete = $this->conn->query($sql);
		$this->capture_err();
		if($delete){
			$resp['status'] = 'success';
			$this->settings->set_flashdata('success'," Image Successfully Deleted. The data will be saved at archives temporarily");
		}else{
			$resp['status'] = "failed";
			$resp['error'] = $this->conn->error;
			$resp['sql'] = $sql;
		}
		return json_encode($resp);
	}
	function move_image(){
		extract($_POST);
		$fname  = strrev(explode("/",strrev($opath),-1)[0]);
		if(!is_dir(base_app.'uploads/user_'.$user_id))
			mkdir(base_app.'uploads/user_'.$user_id);
		if(!is_dir(base_app.'uploads/user_'.$user_id.'/album_'.$album_id))
			mkdir(base_app.'uploads/user_'.$user_id.'/album_'.$album_id);
		$npath = 'uploads/user_'.$user_id.'/album_'.$album_id.'/'.$fname;
		$move = rename(base_app.$opath,base_app.$npath);
		if($move){
			$sql = "UPDATE `images` set album_id = '{$album_id}', `path_name` = '{$npath}' where id = '{$_POST['id']}' ";
			$save = $this->conn->query($sql);
			if($save){
				$resp['status'] = 'success';
				$this->settings->set_flashdata('success'," Image Successfully Moved.");
			}else{
				$resp['status'] = "failed";
				$resp['error'] = $this->conn->error;
				$resp['msg'] = "Error Moving image.";
				$resp['sql'] = $sql;
			}
		}
		else{
			$resp['status'] = "failed";
				$resp['msg'] = "Error Moving image.";
				$resp['msg'] = $sql;
		}
		return json_encode($resp);
	}
	function retrieve_image(){
		$sql = "UPDATE `images` set delete_f = 0 where id = '{$_POST['id']}' ";
		$retrieve = $this->conn->query($sql);
		$this->capture_err();
		if($retrieve){
			$sql = "UPDATE `album_list` set delete_f = 0 where id = '{$_POST['album_id']}' ";
			$retrieve_imgs = $this->conn->query($sql);
			if($retrieve_imgs){
				$this->settings->set_flashdata('success'," Image Successfully Retrieved.");
				$resp['status'] = 'success';
			}else{
				$resp['status'] = 'failed';
				$resp['msg'] = 'Image Successfully Retrieved but Retrieving album error';
				$resp['error'] = $this->conn->error;
				$resp['sql'] = $sql;
			}
		}else{
			$resp['status'] = "failed";
			$resp['sql'] = $sql;
		}
		return json_encode($resp);
	}

	function permanently_delete(){
		$images = $this->conn->query("SELECT * FROM `images` where user_id = '{$this->settings->userdata('id')}' and delete_f = 1 ");
		$albums = $this->conn->query("SELECT * FROM `album_list` where user_id = '{$this->settings->userdata('id')}' and delete_f = 1 ");
		while($irow = $images->fetch_assoc()){
			unlink(base_app.$irow['path_name']);
			$this->conn->query("DELETE FROM `images` where id = '{$irow['id']}'");
		}
		while($arow = $albums->fetch_assoc()){
			rmdir(base_app.'uploads/user_'.($this->settings->userdata('id')).'/album_'.$arow['id']);
			$this->conn->query("DELETE FROM `album_list` where id = '{$arow['id']}'");
		}
		$this->settings->set_flashdata('success'," All archived data successfully deleted.");
		$resp['status'] = 'success';
		return json_encode($resp);
	}
	
}

$Master = new Master();
$action = !isset($_GET['f']) ? 'none' : strtolower($_GET['f']);
$sysset = new SystemSettings();
switch ($action) {
	case 'save_album':
		echo $Master->save_album();
	break;
	case 'delete_album':
		echo $Master->delete_album();
	break;
	case 'retrieve_album':
		echo $Master->retrieve_album();
	break;
	case 'save_image':
		echo $Master->save_image();
	break;
	case 'rename_image':
		echo $Master->rename_image();
	break;
	case 'move_image':
		echo $Master->move_image();
	break;
	case 'delete_image':
		echo $Master->delete_image();
	break;
	
	case 'retrieve_image':
		echo $Master->retrieve_image();
	break;
	case 'permanently_delete':
		echo $Master->permanently_delete();
	break;
	default:
		// echo $sysset->index();
		break;
}