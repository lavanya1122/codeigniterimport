<?php
defined('BASEPATH') OR exit('No direct script access allowed');
require FCPATH.'vendor/autoload.php';

use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
class Home extends CI_Controller {
	public function __construct() {
		parent::__construct();
		$this->load->model('home_model');
	}

	public function index()
	{
		//$this->load->view('index');
		$this->load->view('home');
	}
	public function import()
	{
		$this->load->view('index');
		//$this->load->view('home');
	}
	public function spreadhseet_format_download()
	{
		header('Content-Type: application/vnd.ms-excel');
		header('Content-Disposition: attachment;filename="importexample.xlsx"');
		$spreadsheet = new Spreadsheet();
		$sheet = $spreadsheet->getActiveSheet();
		$sheet->setCellValue('A1', 'S.No');
		$sheet->setCellValue('B1', 'Date Of Installation');
		$sheet->setCellValue('C1', 'Seal Name');
		$sheet->setCellValue('D1', 'Installed At');
		$sheet->setCellValue('E1', 'Type');
		$sheet->setCellValue('F1', 'Use');
		$sheet->setCellValue('G1', 'Client');

		$writer = new Xlsx($spreadsheet);
		$writer->save("php://output");
	}
	public function spreadsheet_import()
	{
		$upload_file=$_FILES['upload_file']['name'];
		$extension=pathinfo($upload_file,PATHINFO_EXTENSION);
		if($extension=='csv')
		{
			$reader= new \PhpOffice\PhpSpreadsheet\Reader\Csv();
		} else if($extension=='xls')
		{
			$reader= new \PhpOffice\PhpSpreadsheet\Reader\Xls();
		} else
		{
			$reader= new \PhpOffice\PhpSpreadsheet\Reader\Xlsx();
		}
		$spreadsheet=$reader->load($_FILES['upload_file']['tmp_name']);
		$sheetdata=$spreadsheet->getActiveSheet()->toArray();
		$sheetcount=count($sheetdata);
		if($sheetcount>1)
		{
			$data=array();
			for ($i=1; $i < $sheetcount; $i++) { 
				$doi=$sheetdata[$i][1];
				//echo $doi;die;
				$date=date('Y-m-d', strtotime($doi));
				//$date=date('d-m-Y', strtotime($doi));
				//echo $date;die;
				$sealname=$sheetdata[$i][2];
				$installedat=$sheetdata[$i][3];
				$type=$sheetdata[$i][4];
				$useof=$sheetdata[$i][5];
				$client=$sheetdata[$i][6];
				$un=rand(111111111111,999999999999);
				$data[]=array(
					'doi'=>$date,
					'sealname'=>$sealname,
					'installedat'=>$installedat,
					'type'=>$type,
					'useof'=>$useof,
					'client'=>$client,
					'uniquenumber'=>$un,
					
				);
			}
			$inserdata=$this->home_model->insert_batch($data);
			if($inserdata)
			{
				
				//echo $un;die;
				$this->session->set_flashdata('message','<div class="alert alert-success">Successfully Added.</div>');
				redirect('home');
			} else {
				$this->session->set_flashdata('message','<div class="alert alert-danger">Data Not uploaded. Please Try Again.</div>');
				redirect('home');
			}
		}
	}
	public function spreadsheet_export()
	{
		//fetch my data
		$productlist=$this->home_model->product_list();
		
		header('Content-Type: application/vnd.ms-excel');
		header('Content-Disposition: attachment;filename="importeddata.xlsx"');
		$spreadsheet = new Spreadsheet();
		$sheet = $spreadsheet->getActiveSheet();
		$sheet->setCellValue('A1', 'S.No');
		$sheet->setCellValue('B1', 'Date Of installation');
		$sheet->setCellValue('C1', 'Seal Name');
		$sheet->setCellValue('D1', 'Installed At');
		$sheet->setCellValue('E1', 'Type');
		$sheet->setCellValue('F1', 'Use');
		$sheet->setCellValue('G1', 'Client');

		$sn=2;
		foreach ($productlist as $prod) {
			//echo $prod->product_name;
			
			$sheet->setCellValue('A'.$sn,$prod->id);
			$sheet->setCellValue('B'.$sn,$prod->doi);
			$sheet->setCellValue('C'.$sn,$prod->sealname);
			$sheet->setCellValue('D'.$sn,$prod->installedat);
			$sheet->setCellValue('E'.$sn,$prod->type);
			$sheet->setCellValue('F'.$sn,$prod->useof);
			$sheet->setCellValue('G'.$sn,$prod->client);
			$sn++;
		}
		
		

		$writer = new Xlsx($spreadsheet);
		$writer->save("php://output");
	}
	 public function ajax_list()
    {
        $list = $this->home_model->get_datatables();
        $data = array();
		
        $no = $_POST['start'];
        foreach ($list as $customers) {
            $no++;
            $row = array();
            $row[] = $no;
            $row[] = $customers->uniquenumber;
            $row[] = $customers->doi;
            $row[] = $customers->sealname;
            $row[] = $customers->client;
			$row[] = '<a href="'.base_url().'home/view/'.$customers->uniquenumber.'" target="_blank">Click here</a>';  
         
 
            $data[] = $row;
        }
		//print_r($customers);die;
 
        $output = array(
                        "draw" => $_POST['draw'],
                        "recordsTotal" => $this->home_model->count_all(),
                        "recordsFiltered" => $this->home_model->count_filtered(),
                        "data" => $data,
                );
        //output to json format
        echo json_encode($output);
    }
	public function view($un)
	{
		$data['imdata']=$this->home_model->viewdata($un);
		//print_r($data);die;
		
		$this->load->view('view',$data);
	}
	
}