<?
/**
 * mysql basit i�ler s�n�f� v.1.1
 *
 */
class phpmydb
{
	/**
	 * Veritaban� ba�lant�s�
	 *
	 * @var resource
	 */
	var $db;
	
	/**
	 * Veritaban�nda i�lem yap�lacak alanlar ve de�erleri
	 * Bu alana d��ardan do�rudan eri�im olmayacak
	 *
	 * @var array
	 */
	var $elemanlar;
	
	/**
	 *	 
	 * @var array
	 */
	var $elemanlar_ayar;
	
	
	/**
	 * MySQL Hata D�n���
	 *
	 * @var string
	 */
	var $hata;
	
	/**
	 * Mysql kaynak
	 *
	 * @var resource
	 */
	var $kaynak;
	
	/**
	 * Mysqlden gelen sonuclar
	 *
	 * @var array
	 */
	var $sonuclar;
	
	/**
	 * S�ra id si
	 *
	 * @var string
	 */
	var $siraid;
	
	/**
	 * S�n�f�n a��l�� fonksiyonu
	 *
	 * @param resource $db
	 * @return aay_mysql
	 */
	function phpmydb($db)
	{
		$this->db = $db;
	}

	/**
	 * Veritaban�nda i�lem yap�lacak alan ve de�eri
	 *
	 * @param string $adi
	 * @param string $deger
	 */
	function eleman($adi,$deger,$kmt=0)
	{
		$this->elemanlar[$adi]=$deger;
		$this->elemanlar_ayar[$adi]=$kmt;
	}
	
	/**
	 * Veritaban�na girilecek alanlar� temizler
	 *
	 */
	function eleman_temizle()
	{
		unset($this->elemanlar);
	}
	
	/*
	function eleman_listele()
	{
		print_r($this->elemanlar);
	}
	*/
	
	/**
	 * Sql sorgusu
	 *
	 * @param string $sorgu
	 * @return resource
	 */
	function sorgu($sorgu)
	{
		$s = count($this->hata);
		$kynk = mysql_query($sorgu,$this->db);
		$this->hata[$s]['s'] = $sorgu;
		$this->hata[$s]['m'] = mysql_error($this->db);
		return $kynk;	
	}
	
	
	
	/**
	 * Basit Sql sorgusu
	 *
	 * @param string $tablo
	 * @param string $liste
	 * @param string $sorgu
	 */
	function sorgula($tablo,$liste="*",$sorgu="") 
	{
   		$tmp = "select ".$liste." from ".$tablo." ".$sorgu;
		$this->kaynak = $this->sorgu($tmp);
	} 
	
	/**
	 * Sql sorgusu sonucunu al�r
	 *
	 * @return array
	 */
	function getir()
	{
		$this->sonuclar = mysql_fetch_assoc($this->kaynak);
		return $this->sonuclar;	
	}
	
	/**
	 * Sonuclar i�inden al�r.
	 *
	 * @return 
	 */
	function getir_al($alan)
	{
		if(isset($this->sonuclar))
		{
			if(array_key_exists($alan,$this->sonuclar))
				return $this->sonuclar[$alan];
		}
			
	}
	
	
	/**
	 * Toplam kay�t say�s�n� verir
	 *
	 * @return integer
	 */
	function toplamkayit($tablo="")
	{
		if($this->kaynak)
		{
			$sayi = mysql_num_rows($this->kaynak);
		}else
		{
			if($tablo != "")
			{
				$kynk = $this->sorgu("SHOW TABLE STATUS LIKE '".$tablo."'");
				$snc = mysql_fetch_assoc($kynk);
				$sayi = $snc['Rows'];
			}else
			{
			$sayi = 0;
			}
		}
		return $sayi;
	}
	
	/**
	 * S�ralama
	 *
	 * @param string $tablo
	 * @return integer
	 */
	function sirala($tablo,$km,$id)
	{
		if($this->siraid != "")
		{
		$sorgu = "select id, ".$this->siraid." from ".$tablo." where id =".$id;
		$rs = $this->sorgu($sorgu);
		$rr = mysql_fetch_assoc($rs);
		$sx = $rr[$this->siraid]; 
		if($km=="up")
		{
			$sorgu = "select id, ".$this->siraid." from ".$tablo." where ".$this->siraid." >= ".$sx." order by ".$this->siraid." asc limit 0,2";
		}else
		{
			$sorgu = "select id, ".$this->siraid." from ".$tablo." where ".$this->siraid." <= ".$sx." order by ".$this->siraid." desc limit 0,2";
		}		
		$rs = $this->sorgu($sorgu);

		if(mysql_num_rows($rs) == 2)
		{
			$rr = mysql_fetch_assoc($rs);
			$sirax = $rr[$this->siraid]; 
			$siraxid = $rr['id']; 	
			$rr = mysql_fetch_assoc($rs);
			$siray = $rr[$this->siraid]; 
			$sirayid = $rr['id']; 	
		
			$sorgu = "update ".$tablo." set ".$this->siraid."=".$siray." where id=".$siraxid;
			$rs = $this->sorgu($sorgu);
		
			$sorgu = "update ".$tablo." set ".$this->siraid."=".$sirax." where id=".$sirayid;
			$rs = $this->sorgu($sorgu);
		}
		}	
	 }
	
	/**
	 * Eleman fonksiyonu ile girilen alan ve de�erleri yaz�lan tabloya ekler
	 *
	 * @param string $tablo
	 * @return integer
	 */ 
	function ekle($tablo)
	{
		$tmk = "";
		$tmv = "";
		foreach ($this->elemanlar as $k => $v) {
		
		if($this->elemanlar_ayar[$k]==0)
		{
			$tmk .= $k.",";
   			$tmv .= "'".$v."',";
		}else{
			$tmk .= $k.",";
   			$tmv .= $v.",";
		}   			
		}
		$tmk = substr($tmk,0,strlen($tmk)-1);
		$tmv = substr($tmv,0,strlen($tmv)-1);
		$sorgu = "insert into ".$tablo." (".$tmk.") values (".$tmv.")";
		$this->sorgu($sorgu);
		$id = mysql_insert_id($this->db);
		if($this->siraid!="")
		{
			$sorgu = "update ".$tablo." set ".$this->siraid."='".$id."' where id=".$id;
			$this->sorgu($sorgu);
		}
		
		return $id;
		
	}
	
	/**
	 * Eleman fonksiyonu ile girilen  alan ve de�erleri yaz�lan tablo ve id ile e�leneni de�i�tirir
	 *
	 * @param string $tablo
	 * @param integer $id
	 */
	function duzenle($tablo,$id)
	{
		if($id)
		{	
			$tm = ""; 
			foreach ($this->elemanlar as $k => $v) {
   				if($this->elemanlar_ayar[$k]==0)
				$tm .= $k."='".$v."',";
				else
				$tm .= $k."=".$v.",";				
			}
			$tm = substr($tm,0,strlen($tm)-1);
			
			$sorgu = "update ".$tablo." set ".$tm;
			
			if(is_numeric($id))
			{
				$sorgu .= " where id=".$id;
			}else 
			{
				$sorgu .= " ".$id;
			}
			
		$kynk = $this->sorgu($sorgu);
		return mysql_affected_rows($this->db);
		}
	}

	/**
	 * Eleman fonksiyonu ile girilen  alan ve de�erleri yaz�lan tablo ve id ile e�leneni siler
	 *
	 * @param string $tablo
	 * @param integer,string $id
	 */
	function sil($tablo,$id)
	{
		if($id)
		{
			$sorgu = "delete from ".$tablo;
			
			if(is_numeric($id))
			{
				$sorgu .= " where id=".$id;
			}else 
			{
				$sorgu .= " ".$id;
			}
		
		$kynk = $this->sorgu($sorgu);
		return mysql_affected_rows($this->db);
		}
	}

	
}
?>
