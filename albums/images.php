<?php if($_settings->chk_flashdata('success')): ?>
<script>
	alert_toast("<?php echo $_settings->flashdata('success') ?>",'success')
</script>
<?php endif;?>
<?php
if(isset($_GET['id']) && $_GET['id'] > 0){
    $qry = $conn->query("SELECT * from `album_list` where user_id = '{$_settings->userdata('id')}' and id = '{$_GET['id']}' ");
    if($qry->num_rows > 0){
        foreach($qry->fetch_assoc() as $k => $v){
            $$k=$v;
        }
    }
}
?>
<div class="w-100 d-flex justify-content-between border-bottom py-2">
	<h3><?php echo $name ?></h3>
    <div>
        <a class="btn btn-flat btn-light border" href="./?page=albums" ><i class="fa fa-angle-left"></i> Back</a>
	    <button class="btn btn-flat btn-primary" type="button" id="add-new"><i class="fa fa-upload"></i> Upload</button>
    </div>
</div>
<div class="row row-cols-4 row-cols-md-3 row-cols-sm-1 row-cols-lg-4 py-2">
	<?php 
		$qry = $conn->query("SELECT * FROM `images` where user_id = '{$_settings->userdata('id')}' and `delete_f` = 0 and album_id = '{$_GET['id']}' order by `original_name` asc ");
		while($row = $qry->fetch_assoc()):
	?>
	<div class="col p-2 item">
		<a href="javascript:void(0)" data-id="<?php echo $row['id'] ?>" class="img-item">
			<div class='img-view'>
				<img src="<?php echo validate_image($row['path_name']) ?>" class="img-thumbnail img-fluid img-thumb" alt="img" loading="lazy">
			</div>
			<div class="w-100 d-flex justify-content-between">
				<span class="text-dark"><b><?php echo $row['original_name'] ?></b></span>
				<div  class="dropleft">
					<a href="#" id="menus_<?php echo $row['id'] ?>" data-toggle="dropdown" aria-haspopup="true" aria-expanded="false" class="text-dark"><i class="fa fa-ellipsis-v"></i> </a>
					<div class="dropdown-menu" aria-labelledby="dropdownMenuLink">
						<a class="dropdown-item" download="<?php echo $row['original_name'] ?>" href="<?php echo validate_image($row['path_name']) ?>" target="_blank"><i class="fa fa-download text-primary"></i> Download</a>
						<div class="dropdown-divider"></div>
                        <a class="dropdown-item move_image" data-id="<?php echo $row['id'] ?>" href="javascript:void(0)"><i class="fa fa-arrows-alt text-dark"></i> Move</a>
						<div class="dropdown-divider"></div>
                        <a class="dropdown-item edit_image" data-id="<?php echo $row['id'] ?>" href="javascript:void(0)"><i class="fa fa-edit text-primary"></i> Rename</a>
						<div class="dropdown-divider"></div>
						<a class="dropdown-item delete_image" data-id="<?php echo $row['id'] ?>" href="javascript:void(0)"><i class="fa fa-trash text-danger"></i> Remove</a>
					</div>
				</div>
			</div>
		</a>
	</div>
	<?php endwhile; ?>
</div>
<div class="row">
    <div class="w-100 p-2 text-center" id="nData" style="display:none"><b>No Images</b></div>
</div>
<script>
	$(document).ready(function(){
        if($('.img-item').length <= 0){
            $('#nData').show('slow')
        }else{
            $('#nData').hide('slow')
        }
		$('#add-new').click(function(){
			uni_modal("<i class='fa fa-upload'></i> Create New Album", "albums/manage_image.php?album_id=<?php echo $_GET['id'] ?>")
		})
		$('.edit_image').click(function(){
			uni_modal("<i class='fa fa-edit'></i> Rename Image", "albums/rename_image.php?id="+$(this).attr('data-id'))
		})
		$('.move_image').click(function(){
			uni_modal("<i class='fa fa-arrows-alt'></i> Move Image", "albums/move_image.php?id="+$(this).attr('data-id'))
		})
        $('.img-item').click(function(){
            uni_modal("", "albums/view_img.php?id="+$(this).attr('data-id'))
        })
		$('.delete_image').click(function(){
			_conf("Are you sure to delete this Image ?","delete_image",[$(this).attr('data-id')])
		})
		$('.img-item').closest('.item').hover(function(){
			$(this).css({
				'background':'#005aff29',
				'border-radius':'5px'
			})
		})
		$('.img-item').closest('.item').mouseleave(function(){
			$(this).css({
				'background':'none',
				'border-radius':'5px'
			})
		})
	})
	function delete_image($id){
		start_loader();
		$.ajax({
			url:_base_url_+"classes/Master.php?f=delete_image",
			method:"POST",
			data:{id: $id},
			dataType:"json",
			error:err=>{
				console.log(err)
				alert_toast("An error occured.",'error');
				end_loader();
			},
			success:function(resp){
				if(typeof resp== 'object' && resp.status == 'success'){
					location.reload();
				}else{
					alert_toast("An error occured.",'error');
					end_loader();
				}
			}
		})
	}
</script>