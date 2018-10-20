<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class Crud extends CI_Controller {

	public function __construct()
	{
		parent::__construct();
		$this->load->model('crud_model','crud');
	}

	public function index()
	{
		$this->load->helper('url');
		$this->load->view('data_user');
	}

	public function ajax_list()
	{
		$this->load->helper('url');

		$list = $this->crud->get_datatables();
		$data = array();
		$no = $_POST['start'];
		foreach ($list as $crud) {
			$no++;
			$row = array();
			$row[] = $crud->nama;
			// $row[] = $crud->jenis_kelamin;
			$row[] = $crud->alamat;
			$row[] = $crud->tanggal_lahir;
			// $row[] = $crud->email;
			// $row[] = $crud->username;
			if($crud->photo)
				$row[] = '<a href="'.base_url('upload/'.$crud->photo).'" target="_blank"><img src="'.base_url('upload/'.$crud->photo).'" class="img-responsive" /></a>';
			else
				$row[] = '(No photo)';

			//add html for action
			$row[] = '<a class="btn btn-sm btn-primary" href="javascript:void(0)" title="Edit" onclick="edit_crud('."'".$crud->id."'".')"><i class="glyphicon glyphicon-pencil"></i> Edit</a>
				  <a class="btn btn-sm btn-danger" href="javascript:void(0)" title="Hapus" onclick="delete_crud('."'".$crud->id."'".')"><i class="glyphicon glyphicon-trash"></i> Delete</a>';
		
			$data[] = $row;
		}

		$output = array(
						"draw" => $_POST['draw'],
						"recordsTotal" => $this->crud->count_all(),
						"recordsFiltered" => $this->crud->count_filtered(),
						"data" => $data,
				);
		//output to json format
		echo json_encode($output);
	}

	public function ajax_edit($id)
	{
		$data = $this->crud->get_by_id($id);
		$data->tanggal_lahir = ($data->tanggal_lahir == '0000-00-00') ? '' : $data->tanggal_lahir; // if 0000-00-00 set tu empty for datepicker compatibility
		echo json_encode($data);
	}

	public function ajax_add()
	{
		//$this->_validate();
		
		$data = array(
				'nama' => $this->input->post('nama'),
				// 'jenis_kelamin' => $this->input->post('jkel'),
				'alamat' => $this->input->post('alamat'),
				'tanggal_lahir' => $this->input->post('tgl_lahir'),
			// 	'email' => $this->input->post('email'),
			// 	'username' => $this->input->post('username'),
			// 	'password' => password_hash($this->input->post('pass'), PASSWORD_DEFAULT)
			 );

		if(!empty($_FILES['photo']['name']))
		{
			$upload = $this->_do_upload();
			$data['photo'] = $upload;
		}

		$insert = $this->crud->save($data);

		echo json_encode(array("status" => TRUE));
	}

	public function ajax_update()
	{
		//$this->_validate();
		$data = array(
				'nama' => $this->input->post('nama'),
				'jenis_kelamin' => $this->input->post('jkel'),
				'alamat' => $this->input->post('alamat'),
				'tanggal_lahir' => $this->input->post('tgl_lahir'),
				// 'email' => $this->input->post('email'),
				// 'username' => $this->input->post('username'),
				// 'password' => password_hash($this->input->post('pass'), PASSWORD_DEFAULT)
			);

		if($this->input->post('remove_photo')) // if remove photo checked
		{
			if(file_exists('upload/'.$this->input->post('remove_photo')) && $this->input->post('remove_photo'))
				unlink('upload/'.$this->input->post('remove_photo'));
			$data['photo'] = '';
		}

		if(!empty($_FILES['photo']['name']))
		{
			$upload = $this->_do_upload();
			
			//delete file
			$crud = $this->crud->get_by_id($this->input->post('id'));
			if(file_exists('upload/'.$crud->photo) && $crud->photo)
				unlink('upload/'.$crud->photo);

			$data['photo'] = $upload;
		}

		$this->crud->update(array('id' => $this->input->post('id')), $data);
		echo json_encode(array("status" => TRUE));
	}

	public function ajax_delete($id)
	{
		//delete file
		$crud = $this->crud->get_by_id($id);
		if(file_exists('upload/'.$crud->photo) && $crud->photo)
			unlink('upload/'.$crud->photo);
		
		$this->crud->delete_by_id($id);
		echo json_encode(array("status" => TRUE));
	}

	private function _do_upload()
	{
		$config['upload_path']          = 'upload/';
        $config['allowed_types']        = '*';
        $config['max_size']             = 1200; //set max size allowed in Kilobyte
        $config['max_width']            = 1000; // set max width image allowed
        $config['max_height']           = 1000; // set max height allowed
        $config['file_name']            = round(microtime(true) * 1000); //just milisecond timestamp fot unique name

        $this->load->library('upload', $config);

        if(!$this->upload->do_upload('photo')) //upload and validate
        {
            $data['inputerror'][] = 'photo';
			$data['error_string'][] = 'Upload error: '.$this->upload->display_errors('',''); //show ajax error
			$data['status'] = FALSE;
			echo json_encode($data);
			exit();
		}
		return $this->upload->data('file_name');
	}

	private function _validate()
	{
		$data = array();
		$data['error_string'] = array();
		$data['inputerror'] = array();
		$data['status'] = TRUE;

		if($this->input->post('nama') == '')
		{
			$data['inputerror'][] = 'nama';
			$data['error_string'][] = 'Nama is required';
			$data['status'] = FALSE;
		}

		if($this->input->post('jkel') == '')
		{
			$data['inputerror'][] = 'Jenis Kelamin';
			$data['error_string'][] = 'Jenis Kelamin is required';
			$data['status'] = FALSE;
		}

		if($this->input->post('alamat') == '')
		{
			$data['inputerror'][] = 'alamat';
			$data['error_string'][] = 'Alamat is required';
			$data['status'] = FALSE;
		}
		
		if($this->input->post('tgl_lahir') == '')
		{
			$data['inputerror'][] = 'dob';
			$data['error_string'][] = 'Date of Birth is required';
			$data['status'] = FALSE;
		}

		if($this->input->post('username') == '')
		{
			$data['inputerror'][] = 'username';
			$data['error_string'][] = 'Username is required';
			$data['status'] = FALSE;
		}

		if($this->input->post('pass') == '')
		{
			$data['inputerror'][] = 'pass';
			$data['error_string'][] = 'Password is required';
			$data['status'] = FALSE;
		}

		if($data['status'] === FALSE)
		{
			echo json_encode($data);
			exit();
		}
	}

}
