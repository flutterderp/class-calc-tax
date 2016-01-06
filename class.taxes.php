<?php
class Taxes
{
	public $sub_total;
	public $ship_total;
	public $total;
	public $ship_state;
	public $ship_zip;
	protected $tax_total							= 0.00;
	protected $ship_tax_states				= array('AR','CO','CT','FL','GA','IL','IN','KA','KT','MI','MO','MS','NC','ND','NE','NM','NV','NY','OH','PA','RI','SC','SD','TN','TX','WA','WI');
	protected $merch_handling_states	= array('AL','AZ','CA','LA','MD','ME','VA','VT');
	protected $merch_tax_states				= array('IA','ID','MA','MN','NJ','OK','UT','WY');
	protected $taxes;
	
	function __construct($sub_total = 0.00, $ship_total = 0.00, $total = 0.00, $ship_state = '', $ship_zip = '')
	{
		$this->sub_total	= $sub_total;
		$this->ship_total	= $ship_total;
		$this->total			= $total;
		$this->ship_state	= $ship_state;
		$this->ship_zip		= $ship_zip;
		
		$db		= JFactory::getDbo();
		$sql	= $db->getQuery(true);
		$sql->select('*')->from($db->quoteName('#__taxes'))->where('tax_state = ' . $db->quote($this->ship_state))->where('state = 1')->order('ordering ASC');
		$db->setQuery($sql);
		
		try
		{
			$this->taxes = $db->loadObjectList();
		}
		catch(RuntimeException $e)
		{
			$this->taxes = false;
		}
	}
	
	function getTax()
	{
		if(is_array($this->taxes) && !empty($this->taxes))
		{
			foreach($this->taxes as $tax)
			{
				$zips = explode( ',', $tax->tax_zip );
				// Skip this tax if not applicable to zip code
				if(!empty($tax->tax_zip) && !in_array($this->ship_zip, $zips))
					continue;
				
				if(in_array($this->ship_zip, $this->ship_tax_states))
				{
					// Shipping taxable
					$this->tax_total += ($this->sub_total + $this->ship_total) * ($tax->tax_rate / 100);
				}
				elseif(in_array($this->ship_zip, $this->merch_handling_states))
				{
					// Merchandise + handling
					$this->tax_total += $this->sub_total * ($tax->tax_rate / 100);
				}
				elseif(in_array($this->ship_zip, $this->merch_tax_states))
				{
					// Merchandise only
					$this->tax_total += $this->sub_total * ($tax->tax_rate / 100);
				}
				else
				{
					$this->tax_total += $this->sub_total * ($tax->tax_rate / 100);
				}
			}
			
			return number_format($this->tax_total, 2);
		}
		else
		{
			// No taxes found
			return false;
		}
	}
	
}
