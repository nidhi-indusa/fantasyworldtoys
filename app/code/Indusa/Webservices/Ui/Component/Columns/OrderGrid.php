<?php
	
	namespace Indusa\Webservices\Ui\Component\Columns;
	use Magento\Ui\Component\Listing\Columns\Column as Column;
	Class OrderGrid extends Column 
	{
		public function prepareDataSource(array $dataSource)
		{
			
			if (isset($dataSource['data']['items'])) {
				foreach ($dataSource['data']['items'] as &$item) {
				
					//Format Ax sync status
					if($item['sync'] == 0 ) $item['sync'] = "Pending";
					elseif($item['sync'] == 1 )$item['sync'] = "<span style='color:green;'>Success</span>";
					elseif($item['sync'] == 2 )$item['sync'] = "<span style='color:red;'>Failed</span>";
					
					//Format delivery date 
					if($item['delivery_date'] && $item['delivery_from'] == "Warehouse" ) $item['delivery_date'] = date('d-m-Y', strtotime($item['delivery_date']));
					else $item['delivery_date'] = '-';
				}
			}
			return $dataSource;
		}
		
	}	