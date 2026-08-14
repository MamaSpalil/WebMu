<?php if (!defined('insite')) die("no access");
/**
* показ вещей 
* @inv_items - хекс-код вещей, если пусто, то ничего не возратит
* @type - тип отображени€
* 4 - возвращает текстовое название вещи + опции
* 3 - показывает название вещи при наведении картинку и инфу.
* 2 - показывает изображение вещи, при наведении - информацию
* 1 - показывает сундук
* 0 - показывает только вещь, но с всплывающим изображением
**/ 
function show_items($inv_items='nope', $type=1,$link="/?up=wsell&si")
{
  require "opt.php";
  $link=$config["siteaddress"].$link;
  if ($inv_items=='nope') echo "We've just got an error: No hex - no information!";
  else
  {
   if (substr($inv_items,0,2)=="0x") $inv_items = substr($inv_items,2);
   else $inv_items=strtoupper($inv_items);
		
   $col_i = strlen($inv_items)/32;

   $str =0;

   if ($type==1) echo "<table border='0' style='border-spacing: 0px;empty-cells: hide;' cellPadding='0' cellSpacing='0'><tbody>";
			
   require 'imgs/items.php';
   
   for ($i=0;$i<$col_i;$i++) 
   {
     if (!$itemarr[$i] || strlen($itemarr[$i]==32))$itemarr[$i] = substr($inv_items,$i*32, 32); //вещь
     if ($itemarr[$i]!="FFFFFFFFFFFFFFFFFFFFFFFFFFFFFFFF" && strlen($itemarr[$i])==32) //если не пуста€ €чейка
     {	
       $item["id"] = hexdec(substr($itemarr[$i],0,2)); // ID
       $item["lopt"] = hexdec(substr($itemarr[$i],2,2)); // level/opt
       $item["lvl380"] = hexdec(substr($itemarr[$i],19,1));//lvl 380
       if($item["lvl380"]==8)
       {
        $item["group"] = (hexdec(substr($itemarr[$i],18,2))-8)/16;
	$lvl380 = "<font color=#CC0099><b>Additional Dmg+200<br>Pow Success Rate +10";
       }
     else
     { // group
       $lvl380 = "";
       $item["group"] = hexdec(substr($itemarr[$i],18,2))/16;
     } 
				
       $item["anc"] = hexdec(substr($itemarr[$i],17,1)); //Ancient data
       $item["exopt"] = hexdec(substr($itemarr[$i],14,2)); //exel + add
       $item["har"] = hexdec(substr($itemarr[$i],20,1)); //harmony
       $item["harlvl"] = hexdec(substr($itemarr[$i],21,1)); // +? harmony
       $item["sockets"] = substr($itemarr[$i],22); //sockets
		
    if ($item["har"]>0)
    {
	 if($item["group"]<5)require "_sysvol/harmony_w.php";
	 else if($item["group"]==5) require "_sysvol/harmony_s.php";
     else if($item["group"]>5 && $item["group"]<12) require "_sysvol/harmony_o.php";
	 $harmony=$hopt[$item["har"]][$item["harlvl"]];
	}
	else $harmony="";

	if ($item["sockets"]=="FFFFFFFFFF" or $item["sockets"]=="0000000000")  $socket = "";
	else $socket = whatsock($item["sockets"]); 
	$name = $itembd[$item["group"]][$item["id"]][0]; //generate item name

	if(!$name)
	 $display="Unknown item name ".$item["group"]." ".$item["id"];
	else
	{
	  if($item["lopt"]>=128)
	  {	
	   $iskill="+ skill";	
	   $show_info = $item["lopt"] - 128;
	  }
	else 
	{
	 $show_info = $item["lopt"]; 
	 $iskill="";
	}
	$ilevel = (integer) ($show_info/8);
	$show_info -= $ilevel*8;
	//level
	if($ilevel==0)
	{
	 $ilevel="";
	 $csstyle="name0";
	}
	else
	{		
	 if ($ilevel>=0 && $ilevel<4) $csstyle="name0";
	 if ($ilevel<7 && $ilevel>=4) $csstyle="name4";
	 elseif($ilevel>=7) $csstyle="name7";
	 $ilevel=" +".$ilevel;
	}
	
	if ($item["group"]==13 && $item["id"]==31) 
	{
	 if ($ilevel=="+1"){$name="Spirit of Dark Raven"; $ilevel="";}
	 else $name="Spirit of Dark Horse";
	}
	elseif($item["group"]==12 && $item["id"]==26)//boxes
	{
	  switch ($ilevel)
	  {
	   case "+1": $name="Red Crystal";$ilevel="";break;
	   case "+2": $name="Blue Crystal";$ilevel="";break;
	   case "+3": $name="Dark Crystal";$ilevel="";break;
	   case "+4": $name="Box of Treasure";$ilevel="";break;
	   case "+5": $name="Box of Surprice";$ilevel="";break;
	  }
	}
	elseif($item["group"]==13 && $item["id"]==20)	//warror's rings
	{
	 if ($ilevel=="+1" || $ilevel=="+2")
	 {$name="Ring Of Warrior"; $ilevel="";}							
	}
	elseif($item["group"]==14 && $item["id"]==11)
	{
	 switch ($ilevel)
	 {
	  case "+1": $name="Star";$ilevel="";break;
	  case "+2": $name="FireCracker";$ilevel="";break;
	  case "+5": $name="Silver Medal";$ilevel="";break;
	  case "+6": $name="Gold Medal";$ilevel="";break;
	  case "+7": $name="Box of Heaven";$ilevel=""; $csstyle="name7"; break;
	  case "+8": $name="Box of Kundun +1";$ilevel=""; $csstyle="name7"; break;
	  case "+9": $name="Box of Kundun +2";$ilevel=""; $csstyle="name7"; break;
	  case "+10": $name="Box of Kundun +3";$ilevel=""; $csstyle="name7"; break;
	  case "+11": $name="Box of Kundun +4";$ilevel=""; $csstyle="name7"; break;
	  case "+12": $name="Box of Kundun +5";$ilevel=""; $csstyle="name7"; break;
	  case "+13": $name="Heart Of Lord";$ilevel=""; break;
	 }
	}
	elseif($item["group"]==12 && $item["id"]==11)
	{
	  switch ($ilevel)
	  {
	   case "": $name="Summoning Goblin";$ilevel="";break;
	   case "+1": $name="Summoning Stone Golim";$ilevel="";break;
	   case "+2": $name="Summoning Assasin";$ilevel="";break;
	   case "+3": $name="Summoning Bali";$ilevel="";break;
	   case "+4": $name="Summoning Soldier";$ilevel="";break;
	   case "+5": $name="Summoning Yeti";$ilevel="";break;
	   case "+6": $name="Summoning Dark Knight";$ilevel="";break;
	  }
	}
	elseif($item["group"]==12)
	{
	  switch($item["id"])
	  {
	    case 30: if($ilevel=="+1"){ $name="Jewel of Bless mix x20"; $ilevel=""; $csstyle="name7";}elseif($ilevel=="+2"){ $name="Jewel of Bless mix x30";$ilevel=""; $csstyle="name7";} break;
	    case 31: if($ilevel=="+1"){ $name="Jewel of Soul mix x20";$ilevel=""; $csstyle="name7";}elseif($ilevel=="+2"){ $name="Jewel of Soul mix x30";$ilevel=""; $csstyle="name7";} break;    
	  }
	}
	elseif($item["group"]==13)
	{
	  switch($item["id"])
	  {
	    case 7: if($ilevel=="+1"){ $name="Sperman";$ilevel="";} break;
	    case 11: if($ilevel=="+1"){ $name="Life Stone";$ilevel=""; $csstyle="name7";}else { $name="Guardian";$ilevel=""; $csstyle="name7";} break;
	    case 14: if($ilevel=="+1"){ $name="Crest of Monarch";$ilevel=""; $csstyle="name7";} break;
	  }
	}
	elseif($item["group"]==14)
	{
	  switch($item["id"])
	  {
	    case 7: if($ilevel=="+1"){ $name="Potion of Soul";$ilevel="";} break;
	    case 12: if($ilevel=="+1"){ $name="Heart";$ilevel="";}elseif($ilevel=="+2"){ $name="Pergamin";$ilevel="";} break;
	    case 21: if($ilevel=="+1"){$name="Stone";$ilevel="";}elseif($ilevel=="+3"){ $name="Sing of Lord";$ilevel="";} break;
	    case 32: if($ilevel=="+1"){ $name="Pink Candy Box";$ilevel="";} break;
	    case 33: if($ilevel=="+1"){ $name="Orange Candy Box";$ilevel="";} break;
	    case 34: if($ilevel=="+1"){ $name="Blue Candy Box";$ilevel="";} break;
	  }
	}
	
	$bezuter = array(8,9,10,20,21,22,23,24,38,39,40,41,42,12,13,25,26,27,28);
	if ($show_info>3) {$iluck="<font color=#004ACB>Luck (success rate of Jewel of Soul + 25%)<br>Luck (critical damage rate +5%)"; $show_info-=4; if($csstyle=="name0" || $csstyle=="name4")$csstyle="name_opt";}else {$iluck="";}
					
	if ( $item["group"] < 6 or ($item["group"]==12)||($item["group"]==13 && $item["id"]==30))
	{
	 $mnoz = 4;
	 $sum = 16;
	}
	elseif($item["group"]==6)
	{
	 $mnoz = 5;
	 $sum = 20;
	}
	elseif($item["group"]==13 && in_array($item["id"],$bezuter))//бижутери€
	{
	 $mnoz = 1;
	 $sum = 4;
	}
	else //wings
	{
	 $mnoz = 4;
	 $sum = 16;
	}
	/* опции*/
	$itemsopt[0] = "<font color=#004ACB>Additional dmg";
	$itemsopt[1] = "<font color=#004ACB>Additional dmg";
	$itemsopt[2] = "<font color=#004ACB>Additional dmg";
	$itemsopt[3] = "<font color=#004ACB>Additional dmg";
	$itemsopt[4] = "<font color=#004ACB>Additional dmg";
	$itemsopt[5] = "<font color=#004ACB>Additional wizardy dmg";
	$itemsopt[6] = "<font color=#004ACB>Additional defence rate";
	$itemsopt[7] = "<font color=#004ACB>Additional defence";
	$itemsopt[8] = "<font color=#004ACB>Additional defence";
	$itemsopt[9] = "<font color=#004ACB>Additional defence";
	$itemsopt[10] = "<font color=#004ACB>Additional defence";
	$itemsopt[11] = "<font color=#004ACB>Additional defence";
	$itemsopt[12] = "<font color=#004ACB>options";
	
	if ($item["group"]==13 && $item["id"]!=30)
	{
	  switch($item["id"])
	  {
	    case 24: $itemsopt[13] = "<font color=#004ACB>Max mana increased"; break;	
	    case 28: $itemsopt[13] = "<font color=#004ACB>Max AG increased";break;	
	    default: $itemsopt[13] = "<font color=#004ACB>Automatic HP recovery";
	  }
	}else $itemsopt[13] = "Additional dmg";
	
	if ($show_info>=0) 
	{
	 /*wings options add*/
	$wing_opt[0]="<font color=#004ACB>Additional wizardy dmg";
	$wing_opt[1]="<font color=#004ACB>Additional dmg";
	$wing_opt[2]="<font color=#004ACB>Automatic HP recovery";
	$wing_opt[3]="<font color=#004ACB>Additional defence";
 
	$dw_wing = array(1,4,41,42); //wings sum & dw
	$dk_wing = array(2,5); // dk
	$elf_wing = array(3,0); // elf
	$thss = array(36,37,38,39,40,43);//винги дл€ 3-го класса
	
	if ($item["group"]==12 or ($item["group"]==13 and $item["id"]==30))
	{
	  if (in_array($item["id"],$dw_wing)) // опции на винги дв
	  {
	   if (($item["exopt"]>=31 and $item["exopt"]<=64 and $item["exopt"]!=32)  or $item["exopt"]==0)
	   {
	     $itemsopt[12]=$wing_opt[2];
	     if ($item["exopt"]<=31) $iopt = $show_info;
	     else 
	     {
		  $iopt = ($show_info+4);
		  $item["exopt"]-=64;
	     }
	   }
	   elseif($item["exopt"]>=96 or $item["exopt"]==32) // add wiz dmg
	   {
	     $itemsopt[12]=$wing_opt[0];
	     if ($item["exopt"]>=96)
	     {
		$iopt =($show_info*$mnoz)+$sum;
		$item["exopt"]-=96;
	     }
	     else
	     {
		$iopt =($show_info*$mnoz);
		$item["exopt"]-=32;
	     }
	   }
	  }
	  else if(in_array($item["id"],$dk_wing)) //опции на винги дк
	  {
	   if ($item["exopt"]>=32 or $item["exopt"]>=96)//add dmg
	   {
	    $itemsopt[12]=$wing_opt[1];
	    if ($item["exopt"]>=32 && $item["exopt"]<96)
	    {
		$iopt = $show_info*4;
		$item["exopt"]-=32;
	    }
	    else 
	    {
	     $iopt = ($show_info+4)+16;
	     $item["exopt"]-=96;
	    }
	   }
	   elseif($item["exopt"]>=0 or ($item["exopt"]>=64 && $item["exopt"]<96)) //HP rec
	   {
	    if ($item["exopt"]>=0)
	    {
	     $iopt =$show_info;			
	    }
	    else
	    {
	     $iopt =$show_info+4;
	     $item["exopt"]-=64;
	    }
	     $itemsopt[12]=$wing_opt[2];
	   }
	  }
	  else if(in_array($item["id"],$elf_wing)) // elf wings opt
	  {
	    if ($item["exopt"]>=32 or $item["exopt"]>=96)//HP rec
	    {
		$itemsopt[12]=$wing_opt[2];
		if ($item["exopt"]>=32 && $item["exopt"]<96)
		{
		 $iopt = $show_info;
		 $item["exopt"]-=32;
		}
		else 
		{
		 $iopt = $show_info+4;
		 $item["exopt"]-=96;
		}
	    }
	    elseif($item["exopt"]>=0 or ($item["exopt"]>=64 && $item["exopt"]<96)) //add dmg
	    {
		if ($item["exopt"]>=0)
		{
		 $iopt =$show_info*4;
		}
		else
		{
		 $iopt =($show_info*4)+16;
		 $item["exopt"]-=64;
		}
		$itemsopt[12]=$wing_opt[1];
	    }
	  }
	  else if (in_array($item["id"],$thss))//3thd class
	  {
	   if (($item["exopt"]>=31 and $item["exopt"]<=64 and $item["exopt"]!=32)  or $item["exopt"]==0)
	   {
	    $itemsopt[12]=$wing_opt[2];
	    if ($item["exopt"]<=31) $iopt = $show_info;
	    else
        {
         $iopt = ($show_info+4);
		 $item["exopt"]-=64;
		}
	   }
	   elseif($item["exopt"]>=96 or $item["exopt"]==32) // add def
	   {
	    if ($item["exopt"]>=96)
	    {
	     $iopt =($show_info*$mnoz)+$sum;
	     $item["exopt"]-=96;
	    }
	    else
	    {
	     $iopt =($show_info*$mnoz);
	     $item["exopt"]-=32;
	    }
	    $itemsopt[12]=$wing_opt[3];
	   }
	 }
	 else // все остальные винги
	 {
	  if ($item["exopt"] <32 or ($item["exopt"]>=64 && $item["exopt"]<96) ) // wizardy dmg
	  {
	   if($item["exopt"]<64)
	      	$iopt =($show_info*4);
	   else
           {
	    $iopt =($show_info*4)+16;
	    $item["exopt"]=-64;
	   }							   
	   $itemsopt[12]=$wing_opt[0];
	  }
	 elseif($item["exopt"]>=32 or $item["exopt"] >=96) // add dmg
	 {
	   if($item["exopt"]>=32 && $item["exopt"]<96)
	   {
	    	$iopt =($show_info*4);
		$item["exopt"]-=32;
	   }
	   else
           {
	    $iopt =($show_info*4)+16;
	    $item["exopt"]=-96;
	   }							   
	   $itemsopt[12]=$wing_opt[1];
	 }
	 }
	}
	else // обычные вещи
	{
	 if ($item["exopt"]>63)
	 {
	 $iopt = (($show_info*$mnoz)+$sum);
	 $item["exopt"] -=64;
	 }
	 elseif($item["exopt"]<=63)
	 {
	 $iopt = ($show_info*$mnoz);						
	 }
	}

	if($iopt>0)
	{
 	 $iopt = $itemsopt[$item["group"]]." +".$iopt."%";
	 if($csstyle=="name0" || $csstyle=="name4")$csstyle="name_opt";
	}
	else $iopt="";
	}else $iopt="";
	$excopt=$item["exopt"];
	/* EXCELLENT OPTIONS */
	if($excopt>0) 
	{
	 $csstyle="name_ex";
	 $name = " <font color=#00CC00><b>Excellent&nbsp;".$name;
						
	 if (($item["group"]<=5)||($item["group"]==13 && ($item["id"]==12 || $item["id"]==13 || $item["id"]>=25 && $item["id"]<=28)))//если это оружие или пенданты
	 {
 	  $excoptar[0]="<div class=\"excellent\"><font color=#004ACB>Mana After Hunting Monsters +mana/8</div>";
	  $excoptar[1]="<div class=\"excellent\"><font color=#004ACB>Life After Hunting Monsters +life/8</div>";
	  $excoptar[2]="<div class=\"excellent\"><font color=#004ACB>Increase Attacking(Wizardy) speed +7</div>";
	  $excoptar[3]="<div class=\"excellent\"><font color=#004ACB>Increase Damage +2%</div>";
	  $excoptar[4]="<div class=\"excellent\"><font color=#004ACB>Increase Damage +Level/20</div>";
	  $excoptar[5]="<div class=\"excellent\"><font color=#004ACB>Excellent Damage Rate +10%</div>";
	 }
	 elseif(($item["group"]>=6 && $item["group"]<=11)||(($item["group"]==13 && $item["id"]!=30)&&(($item["id"]>=8 && $item["id"]<=10) || ($item["id"]>=20 && $item["id"]<=24)||($item["id"]>=38 && $item["id"]<=41))))	// щиты, сеты, кольца
	 {
	  $excoptar[0]="<div class=\"excellent\"><font color=#004ACB>Increase Rate of Zen 40%</div>";
	  $excoptar[1]="<div class=\"excellent\"><font color=#004ACB>Defense Success Rate +10%</div>";
	  $excoptar[2]="<div class=\"excellent\"><font color=#004ACB>Reflect Damage +5%</div>";
	  $excoptar[3]="<div class=\"excellent\"><font color=#004ACB>Damage Decrease +4%</div>";
	  $excoptar[4]="<div class=\"excellent\"><font color=#004ACB>Increase Max Mana +4%</div>";
	  $excoptar[5]="<div class=\"excellent\" ><font color=#004ACB>Increase Max Hp +4%</div>";
	 }
	 elseif($item["group"]==12 || ($item["group"]==13 && $item["id"]==30))// винги и плащи
	 {
	  $excoptar[0]="<div class=\"excellent\" ><font color=#004ACB>+ 115 HP</div>";
	  $excoptar[1]="<div class=\"excellent\"><font color=#004ACB>+ 115 MP</div>";
	  $excoptar[2]="<div class=\"excellent\"><font color=#004ACB>Ignore Enemy&#39;s defense 3%</div>";
	  $excoptar[3]="<div class=\"excellent\"><font color=#004ACB>+50 Max Stamina</div>";
	  $excoptar[4]="<div class=\"excellent\"><font color=#004ACB>Wizardry Speed +7</div>";
	  $excoptar[5]="<div class=\"excellent\"></div>";					
     }
    // if ($excopt!=64)
	// {
	  if (($excopt-32)>=0) $excopt -=32; else $excoptar[5]="false";
	  if (($excopt-16)>=0) $excopt -=16; else $excoptar[4]="false";
	  if (($excopt-8)>=0) $excopt -=8; else $excoptar[3]="false"; 
	  if (($excopt-4)>=0) $excopt -=4; else $excoptar[2]="false";
	  if (($excopt-2)>=0) $excopt -=2; else $excoptar[1]="false";
	  if ($excopt==0)$excoptar[0]="false";
	  foreach ($excoptar as $k) { if ($k!="false") $display_e .=$k;}
	 //}
	}
					if ($item["anc"]>0)
					{ 
						$csstyle="ancient";
						if ($item["anc"]==10) {$name = $anc["na"][$item["group"]][$item["id"]]." ".$name; $ancopt="+10 anc opt";}
						else $name = $anc["a"][$item["group"]][$item["id"]]." ".$name;
						if ($item["anc"]==5 || $item["anc"]==6)$ancopt="<b><font color=#BFFFFE>+5 Ancient Set Option";
						if ($item["anc"]==9)$ancopt="+10 anc opt";
					}else $ancopt="";
					
					
					if ($type==4)
					{
					 $temp="";
					 if (strlen($iskill)>2) $temp.="+Skill ";
					 if (strlen($iluck)>2)$temp.="+Luck ";
					 if (strlen($iopt)>1)$temp.="+Options ";
					 return $name.$ilevel.$temp;
					}
					/*show "can equip by"*/
					$group12 = array(12,15,26,31);
					$group13 = array(11,14,15,29,31,31,33,34,35,36,39,40,41,42,43,44,45,46,47,48,49,50,51,52,53,54,55,56,57,58,59,60,61);
					if ($item["group"]==12 and in_array($item["id"],$group12)
					    or $item["group"]==13 and in_array($item["id"],$group13)
					    or $item["group"]==14 ) $can_equipm="";
					else   
						if (can_equip( substr($itembd[$item["group"]][$item["id"]][1],2))!="")	$can_equipm = "<span style=background-color:#D72626; padding:3px;><font color=white>Can be equipment by  ".can_equip( substr($itembd[$item["group"]][$item["id"]][1],2));
					/*end show "can equip by"*/
					if ($type==0) $eqtcl="bcanbe"; else $eqtcl="wcanbe";
					//===
					if ($type!=1 && $type!=2 && $type!=3 && $type!=5  )
					{	
						$item["lopt"] = knowop($item["id"],$item["group"],$item["lopt"]);

						$img = "imgs/items/".$item["group"].$item["id"].$item["lopt"].".gif";
						if (!file_exists($img))$img="you must name img: ".$item["group"].". ".$item["id"].". ".$item["lopt"].".gif"; //если нет изображени€
						else $img = "<img src=\"".$img ."\" align=center >";
						$display ="
							<table align='center' vAlign='top' width='100%' width='100%' border='0' cellspacing='0' cellpadding='0'>
							<tr><td class=".$csstyle." ><span title='".$img ."'>".$name.$ilevel."</span></td></tr>";
						$display.="<tr><td align=\"center\" valign=\"center\" class=\"".$eqtcl."\" >".$can_equipm."</td></tr>";
						if (strlen($iskill)>2) $display.="<tr><td align=\"center\" valign=\"center\" class=\"cskill\">".$iskill."</td></tr>";
						if (strlen($iluck)>2) $display.="<tr><td align=\"center\" valign=\"center\" class=\"cluck\">".$iluck."</td></tr>";
						if (strlen($iopt)>1) $display.="<tr><td align=\"center\" valign=\"center\" class=\"iopt\">".$iopt."</td></tr>";
						if (strlen($display_e)>2) $display.="<tr><td align=\"center\" valign=\"center\">".$display_e."</td></tr>";
						if (strlen($ancopt)>2) $display.="<tr><td align=\"center\" valign=\"center\" class=\"anc_opt\">".$ancopt."</td></tr>";
						if (strlen($lvl380)>2) $display.="<tr><td align=\"center\" valign=\"center\" class=\"refinery\">".$lvl380."</td></tr>";
						if (strlen($harmony)>2) $display.="<tr><td align=\"center\" valign=\"center\" class=\"harmony\">".$harmony."</td></tr>";
						if (strlen($socket)>2) $display.="<tr><td align=\"center\" valign=\"center\" class=\"socket\">".$socket."</td></tr>";
						 
						$display .="</table>";	
						
						return $display;
					}
					else if ($type==5)
					{
					$item["lopt"] = knowop($item["id"],$item["group"],$item["lopt"]);

						$img = "imgs/items/".$item["group"].$item["id"].$item["lopt"].".gif";
						if (!file_exists($img))$img="you must name img: ".$item["group"].". ".$item["id"].". ".$item["lopt"].".gif"; //если нет изображени€
						else $img = "<img src=\"".$img ."\" align=center >";
						$display ="<table align='center' vAlign='top' width='250' border='0' cellspacing='0' cellpadding='0'>";
						if (strlen($iskill)>2) $display.="<tr><td align=\"center\" valign=\"center\" class=\"cskill\">".$iskill."</td></tr>";
						if (strlen($iluck)>2) $display.="<tr><td align=\"center\" valign=\"center\" class=\"cluck\">".$iluck."</td></tr>";
						if (strlen($iopt)>1) $display.="<tr><td align=\"center\" valign=\"center\" class=\"iopt\">".$iopt."</td></tr>";
						if (strlen($display_e)>2) $display.="<tr><td align=\"center\" valign=\"center\">".$display_e."</td></tr>";
						if (strlen($ancopt)>2) $display.="<tr><td align=\"center\" valign=\"center\" class=\"anc_opt\">".$ancopt."</td></tr>";
						if (strlen($lvl380)>2) $display.="<tr><td align=\"center\" valign=\"center\" class=\"refinery\">".$lvl380."</td></tr>";
						if (strlen($harmony)>2) $display.="<tr><td align=\"center\" valign=\"center\" class=\"harmony\">".$harmony."</td></tr>";
						if (strlen($socket)>2) $display.="<tr><td align=\"center\" valign=\"center\" class=\"socket\">".$socket."</td></tr>";
						 
						$display .="</table>";	
					}
					else 
					{
						$display="<table width=\"250\" border=0 align=\"center\" valign=\"center\">";
						if($type!=3) $display.= "<tr><td align=\"center\" valign=\"center\" class=\"".$csstyle." \" >".$name.$ilevel."</td></tr>";
						$display.="<tr><td align=\"center\" valign=\"center\" class=\"".$eqtcl."\">".$can_equipm."</td></tr>";
						if (strlen($iskill)>2) $display.="<tr><td align=\"center\" valign=\"center\" class=\"cskill\">".$iskill."</td></tr>";
						if (strlen($iluck)>2) $display.="<tr><td align=\"center\" valign=\"center\" class=\"cluck\">".$iluck."</td></tr>";
						if (strlen($iopt)>1) $display.="<tr><td align=\"center\" valign=\"center\" class=\"iopt\">".$iopt."</td></tr>";
						if (strlen($display_e)>2) $display.="<tr><td align=\"center\" valign=\"center\">".$display_e."</td></tr>";
						if (strlen($ancopt)>2) $display.="<tr><td align=\"center\" valign=\"center\" class=\"anc_opt\">".$ancopt."</td></tr>";
						if (strlen($lvl380)>2) $display.="<tr><td align=\"center\" valign=\"center\" class=\"refinery\">".$lvl380."</td></tr>";
						if (strlen($harmony)>2) $display.="<tr><td align=\"center\" valign=\"center\" class=\"harmony\">".$harmony."</td></tr>";
						if (strlen($socket)>2) $display.="<tr><td align=\"center\" valign=\"center\" class=\"socket\">".$socket."</td></tr>";
						$display.="</table>"; 
					}
					unset($display_e);
				}

				$item["lopt"] = knowop($item["id"],$item["group"],$item["lopt"]);
				
				if ($item["lopt"]=='' || !$item["lopt"]) $item["lopt"]=0;
				if ($item["lopt"]>128) {$item["lopt"] -=128; $item["lopt"]=(int)($item["lopt"]/8);} 
				if (!$x || $x==0 || !$y || $y==0) {$x=1;$y=1;} 
				$img = "imgs/items/".$item["group"].$item["id"].$item["lopt"].".gif"; // looking for item img
				
			if ($type==1 || $type==2 || $type==3 || $type==5)
			{
				if ($item["group"] == 7 || ($item["group"]>=9 && $item["group"]<=11 && $item["id"]!=128) || $item["group"]==15)
				{
					$x = substr($itembd[$item["group"]][0][1],0,1);
					$y = substr($itembd[$item["group"]][0][1],1,1);
				}
				else
				{
					$x = substr($itembd[$item["group"]][$item["id"]][1],0,1);
					$y = substr($itembd[$item["group"]][$item["id"]][1],1,1);
				}
				if (!$x || !$y){$x=1;$y=1;}//если нет в базе вещи
				if ($type==3)
				{
					$dd = "<table>
							<tr><td class=\"$csstyle\" align=\"center\" >$name$ilevel</td></tr>
							<tr><td align=center><img src=".$img."  align=center style=width:".($x*32)."px; height:".($y*32)."px;></td></tr>
							<tr><td>$display</td></tr>
							</table>";
					return "<div id='tooltiper' title='$dd' class='$csstyle'>$name$ilevel</div>";		
				}
				if ($type==5)
				{
					return  "<table cellspacing=\"0\" cellpadding=\"0\">
							<tr><td class=\"$csstyle\" align=\"center\" >$name$ilevel</td></tr>
							<tr><td align=center class=\"$csstyle\"><img src=".$img."  align=center style=width:".($x*32)."px; height:".($y*32)."px;></td></tr>
							<tr><td class=\"$csstyle\">$display</td></tr>
							</table>";
					// "<div id='tooltiper' title='$dd' class='$csstyle'>$name$ilevel</div>";		
				}
				if (!file_exists($img))$img="<span style='font-size:8px;'>".$item["group"].". ".$item["id"].". ".$item["lopt"].".</span>"; //если нет изображени€
				else $img = "<img title = '".$display."' src='".$img."' id='tooltiper' alt='".$name."' align='center' style='width:".($x*32)."px; height:".($y*32)."px;'>";			
				$j=$x*$y;
				$str=$i;
				$x1=0;
			  }
			}
		if ($type==1)
		{
			while($j>0)
			{
				if($x1<=$x)
				{
	
					if ($str==$i)$itemarr[$str]="<td align='center' colspan='".$x."' rowspan='".$y."' style='color:white;background:url(imgs/2.png);width:32px;height:32px;'><a href='".$link."=".$i."'>".$img."</a></td>";
					else $itemarr[$str]="none";
					$str++;

					$x1++;
					if($x1==$x)
					{
						$str +=(8-$x);
						$x1=0;
					}
				}
				else $itemarr[$str]="<td style='color:white;background:url(imgs/1.png) no-repeat;width:32px;height:32px;' width='32' height='32' align='center'>&nbsp;</td>";
				$j--;
			}
			if($itemarr[$i]=="FFFFFFFFFFFFFFFFFFFFFFFFFFFFFFFF") $itemarr[$i]="<td style='color:white;background:url(imgs/1.png) no-repeat;' width='32' height='32' align='center'>&nbsp;</td>";
			if($i==0) echo "<tr>";
			if($itemarr[$i]!="none" )echo $itemarr[$i];		
			if ($i%8==7 && $i+1!=$col_i)echo "</tr><tr>";
			if($i+1==$col_i) echo"</tr>";
		}
		elseif ($type==2)
		{
			return $img;
		}

		 }
		 if ($type==1) {?></table><?php }
	} 
}

/**
* функци€ возвращает цифру, учавтсвующую в генерации имени изображени€(опции) 
* @num - id вещи
* @group - группа вещи
* @opt - опции что есть
**/
function knowop($num,$group,$opt)
{
if($group>14 || $group<12) return 0;
		
$groupp12=array(0,1,2,3,4,5,6,15,36,37,38,39,40,41,42,43);
$groupp13=array(0,1,2,3,4,5,8,9,10,12,13,21,22,23,24,25,26,27,28,30,37,39,40,41,42,64,65,66,67,80);
$groupp14=array(0,1,2,3,4,5,6,8,13,14,16,35,36,37,38,39,40);
 switch($group)
 {
  case 12: if(in_array($num,$groupp12)) return 0; break;
  case 13: if(in_array($num,$groupp13)) return 0; break;
  case 14: if(in_array($num,$groupp14)) return 0; break;
 }
 return $opt;
}


function smartsearch($item_hex,$x,$y,$tt=0)
{
 if ($tt==0)
 {
	if (substr($item_hex,0,2)=='0x') $item_hex=substr($item_hex,2);
	else $item_hex=strtoupper(urlencode(bin2hex($item_hex)));
  }
	$col_i = strlen($item_hex)/32;
	require 'imgs/items.php';
	for ($i=0;$i<$col_i;$i++) 
	{
		if (!$itemarr[$i] || strlen($itemarr[$i]==32))$itemarr[$i] = substr($item_hex,$i*32, 32);
		if ($itemarr[$i]!="FFFFFFFFFFFFFFFFFFFFFFFFFFFFFFFF" && strlen($itemarr[$i])==32)
		{
			$it["id"] = hexdec(substr($itemarr[$i],0,2)); // ID
			$it["group"] = hexdec(substr($itemarr[$i],18,2))/16; // group
			if ($it["group"] == 7 || ($it["group"]>=9 && $it["group"]<=11 && $it["id"]!=128) || $it["group"]==15)
			{
				$xin = substr($itembd[$it["group"]][0][1],0,1);
				$yin = substr($itembd[$it["group"]][0][1],1,1);
			}
			else
			{
				$xin = substr($itembd[$it["group"]][$it["id"]][1],0,1);
				$yin = substr($itembd[$it["group"]][$it["id"]][1],1,1);
			}
			$j=$xin*$yin;
			$str=$i;
			$x1=0;
			while($j>0)
			{
				if($x1<=$xin)
				{	
					$itemarr[$str]="not_empty";
					$str++;
					$x1++;
					if($x1==$xin)
					{
						$str +=(8-$xin);
						$x1=0;
					}
				}
				$j--;	
			}	
		}
	}
	
	$c=0;

	/*	echo "<table border='1'>";
	for ($i=0;$i<$col_i;$i++) 
	{
			if ($c==8) {
			echo "</tr>";
			$c=0;
			}
			if ($c==0) echo "<tr>";
			echo "<td>$itemarr[$i]</td>";
		$c++;	
	}
	echo "</table>";*/
	for ($i=0;$i<$col_i;$i++) 
	{
			$j = $x * $y;
			$str = $i;
			$x1=0;
			$found=0;		
			$ind = ((floor($i/8)+1)*8)-1; // правый конец строки
			$raz = $i+($x-1);
			while($j>0)
			{
				if($x1<=$x)
				{	
					if (strlen($itemarr[$str])==32 && $str<$col_i && $raz<=$ind) $found++; else {$j=0;$found=0;}
					$str++;
					$x1++;

					if($x1==$x)
					{
						
						$str +=(8-$x);
						$ind+=8;
						$x1=0;
					}
				}
				$j--;	
			}	
			//if ($found == $x*$y && $found!=0){unset($itemarr); return $i;}
			if ($found == $x*$y){unset($itemarr); return $i;}
	}
	unset($itemarr);
	return -1;
}
function whatsock($socets_hex)
{
	if ($socets_hex=="FFFFFFFFFF" || $socets_hex=="0000000000") return "";
	
		$show_sock="";
		$socket = array(
					'FF' => 'Empty socket',
					'01' => 'Fire (Increase Damage/SkillPower (*lvl)) + 20',
					'33' => 'Fire (Increase Damage/SkillPower (*lvl)) + 400',
					'65' => 'Fire (Increase Damage/SkillPower (*lvl)) + 400',
					'97' => 'Fire (Increase Damage/SkillPower (*lvl)) + 400',
					'C9' => 'Fire (Increase Damage/SkillPower (*lvl)) + 400',
					'02' => 'Fire (Increase Attack Speed) + 7',
					'34' => 'Fire (Increase Attack Speed) + 1',
					'03' => 'Fire (Increase Maximum Damage/Skill Power) + 30',
					'35' => 'Fire (Increase Maximum Damage/Skill Power) + 1',
					'04' => 'Fire (Increase Minimum Damage/Skill Power) + 20',
					'36' => 'Fire (Increase Minimum Damage/Skill Power) + 1',
					'05' => 'Fire (Increase Damage/Skill Power) + 20',
					'37' => 'Fire (Increase Damage/Skill Power) + 1',
					'06' => 'Fire (Decrease AG Use) + 40',
					'38' => 'Fire (Decrease AG Use) + 1',
					'0B' => 'Water (Increase Defense Success Rate) + 10',
					'D3' => 'Water (Increase Defense Success Rate) + 1',
					'0C' => 'Water (Increase Defense) + 30',
					'3E' => 'Water (Increase Defense) + 1',
					'0D' => 'Water (Increase Defense Shield) + 7',
					'D5' => 'Water (Increase Defense Shield) + 1',
					'0E' => 'Water (Damage Reduction) + 4',
					'D6' => 'Water (Damage Reduction) + 1',
					'0F' => 'Water (Damage Reflections) + 5',
					'41' => 'Water (Damage Reflections) + 1',
					'11' => 'Ice (Increases + Rate of Life After Hunting) + 8',
					'43' => 'Ice (Increases + Rate of Life After Hunting) + 49',
					'75' => 'Ice (Increases + Rate of Life After Hunting) + 50',
					'A7' => 'Ice (Increases + Rate of Life After Hunting) + 51',
					'D9' => 'Ice (Increases + Rate of Life After Hunting) + 52',
					'12' => 'Ice (Increases + Rate of Mana After Hunting) + 8',
					'44' => 'Ice (Increases + Rate of Mana After Hunting) + 49',
					'76' => 'Ice (Increases + Rate of Mana After Hunting) + 50',
					'A8' => 'Ice (Increases + Rate of Mana After Hunting) + 51',
					'DA' => 'Ice (Increases + Rate of Mana After Hunting) + 52',
					'13' => 'Ice (Increase Skill Attack Power) + 37',
					'45' => 'Ice (Increase Skill Attack Power) + 1',
					'14' => 'Ice (Increase Attack Success Rate) + 25',
					'46' => 'Ice (Increase Attack Success Rate) + 1',
					'15' => 'Ice (Item Duarability Reinforcement) + 30',
					'47' => 'Ice (Item Duarability Reinforcement) + 1',
					'16' => 'Wind (Increase Life AutoRecovery) + 8',
					'48' => 'Wind (Increase Life AutoRecovery) + 1',
					'17' => 'Wind (Increase Maximum Life) + 4',
					'49' => 'Wind (Increase Maximum Life) + 1',
					'18' => 'Wind (Increase Maximum Mana) + 4',
					'4A' => 'Wind (Increase Maximum Mana) + 1',
					'19' => 'Wind (Increase Mana AutoRecovery) + 7',
					'4B' => 'Wind (Increase Mana AutoRecovery) + 1',
					'1A' => 'Wind (Increase Maximum AG) + 25',
					'4C' => 'Wind (Increase Maximum AG) + 1',
					'1B' => 'Wind (Increase AG Amount) + 3',
					'4D' => 'Wind (Increase AG Amount) + 1',
					'1E' => 'Lightning (Increase Excellent Damage) + 15',
					'50' => 'Lightning (Increase Excellent Damage) + 1',
					'1F' => 'Lightning (Increase Excellent Damage Success Rate) + 10',
					'51' => 'Lightning (Increase Excellent Damage Success Rate) + 1',
					'20' => 'Lightning (Increase Critical Damage) + 30',
					'52' => 'Lightning (Increase Critical Damage) + 1',
					'21' => 'Lightning (Increase Critical Damage Success Rate) + 8',
					'53' => 'Lightning (Increase Critical Damage Success Rate) + 1',
					'85' => 'Lightning (Increase Critical Damage Success Rate) + 1',
					'B7' => 'Lightning (Increase Critical Damage Success Rate) + 1',
					'E9' => 'Lightning (Increase Critical Damage Success Rate) + 1',
					'25' => 'Ground (Increase Stamina) + 30',
					'57' => 'Ground (Increase Stamina) + 1',
					'89' => 'Ground (Increase Stamina) + 1',
					'BB' => 'Ground (Increase Stamina) + 1',
					'ED' => 'Ground (Increase Stamina) + 1');
		$i=0;
		while ($i<10)
		{
			if ($socket[substr($socets_hex,$i,2)]!="") $show_sock.="<br><span style=color:#9400D3;font-size:8px;font-weight:bold;>".$socket[substr($socets_hex,$i,2)]."</span>";
			$i+=2;
		}
		return $show_sock;
	
}
function item_name($hex_code)
{
$item["id"] = hexdec(substr($hex_code,0,2)); // ID
$item["lvl380"] = hexdec(substr($hex_code,19,1));//lvl 380
$item["lopt"] = hexdec(substr($hex_code,2,2)); // level/opt
if ($item["lvl380"]==8) $item["group"] = (hexdec(substr($hex_code,18,2))-8)/16;
else $item["group"] = hexdec(substr($hex_code,18,2))/16; // group
($item["lopt"]>=128) ? $show_info = $item["lopt"] - 128 : $show_info = $item["lopt"];						
$ilevel = (integer) ($show_info/8);
require 'imgs/items.php';

if ($item["group"]==12 && $item["id"]==11 || $item["id"]==26 || $item["id"]==30 || $item["id"]==31)
{
	if ($item["id"]==11)
	{
		switch ($ilevel)
		{
			case 0 : $item_name="Summoning Goblin";break;
			case 1 : $item_name="Summoning Stone Golim";break;
			case 2 : $item_name="Summoning Assasin";break;
			case 3 : $item_name="Summoning Bali";break;
			case 4 : $item_name="Summoning Soldier";break;
			case 5 : $item_name="Summoning Yeti";break;
			case 6 : $item_name="Summoning Dark Knight";break;
		}
	}
	elseif($item["id"]==26)
	{
		switch ($ilevel)
		{
		case 1 : $item_name="Red Crystal";break;
		case 2 : $item_name="Blue Crystal";break;
		case 3 : $item_name="Dark Crystal";break;
		case 4 : $item_name="Box of Treasure";break;
		case 5 : $item_name="Box of Surprice";break;
		}
	}
	elseif ($item["id"]==30){if($ilevel==0) $item_name="Jewel of Bless x10"; elseif($ilevel==1) $item_name="Jewel of Bless x20"; elseif($ilevel== 2) $item_name="Jewel of Bless x30";}
	elseif ($item["id"]==31){if($ilevel==0) $item_name="Jewel of Soul x10"; elseif($ilevel==1) $item_name="Jewel of Soul x20"; elseif($ilevel==2) $item_name="Jewel of Soul x30";}
}
elseif($item["group"]==13)
{
	if($item["id"]==7){if($ilevel==1)  $item_name="Sperman";}
	elseif($item["id"]==11){if($ilevel==1) $item_name="Life Stone"; else $item_name="Guardian";}
	elseif($item["id"]==14){if($ilevel==1) $item_name="Crest of Monarch";}
	elseif($item["id"]==20){ if ($ilevel== 1 || $ilevel== 2) $item_name="Ring Of Warrior";}
	elseif($item["id"]==31){($ilevel==1)? $item_name="Spirit of Dark Raven" : $item_name="Spirit of Dark Horse";}
}
elseif($item["group"]==14)
{
	if($item["id"]==7){if($ilevel==1)  $item_name="Potion of Soul";}
	elseif($item["id"]==11)
	{
		switch ($ilevel)
		{
			case 1 : $item_name="Star";break;
			case 2 : $item_name="FireCracker";break;
			case 5 : $item_name="Silver Medal";break;
			case 6 : $item_name="Gold Medal";break;
			case 7 : $item_name="Box of Heaven";break;
			case 8 : $item_name="Box of Kundun +1";break;
			case 9 : $item_name="Box of Kundun +2";break;
			case 10 : $item_name="Box of Kundun +3";break;
			case 11 : $item_name="Box of Kundun +4";break;
			case 12 : $item_name="Box of Kundun +5";break;
			case 13 : $item_name="Heart Of Lord";break;
		}
	}
	elseif($item["id"]==12){if($ilevel==1) $item_name="Heart"; elseif($ilevel==2) $item_name="Pergamin";}
	elseif($item["id"]==21){if($ilevel==1) $item_name="Stone"; elseif($ilevel==3) $item_name="Sing of Lord";}
	elseif($item["id"]==32){if($ilevel==1) $item_name="Pink Candy Box";}
	elseif($item["id"]==33){if($ilevel==1) $item_name="Orange Candy Box";}
	elseif($item["id"]==34){if($ilevel==1) $item_name="Blue Candy Box";}
}
else $item_name = $itembd[$item["group"]][$item["id"]][0];
return $item_name;
}
/**
* функси€ определени€ классов
*/
function can_equip($array)
{

$ch_name[0][1] = "DW";	
$ch_name[0][2] = "SM";
$ch_name[0][3] = "GrM"; 
		
$ch_name[1][1] = "DK";
$ch_name[1][2] = "BK";
$ch_name[1][3] = "BM";

$ch_name[2][1] = "FE";	
$ch_name[2][2] = "ME";
$ch_name[2][3] = "HE";

$ch_name[3][1] = "MG";
$ch_name[3][2] = "DM";
$ch_name[3][3] = "DM";

$ch_name[4][1] = "DL";		
$ch_name[4][2] = "LE";
$ch_name[4][3] = "LE";

$ch_name[5][1] = "Sum";
$ch_name[5][2] = "BS";
$ch_name[5][3] = "DimM";

$ch_name[6][1] = "RF";
$ch_name[6][2] = "FM";
$ch_name[6][3] = "Unknown class";

$display="";
for ($i=0;$i<strlen(trim($array));$i++)
{
	if ($array[$i]!=0)
	{
		if ($display!="") $display .="/";
			
		$display .= $ch_name[$i][$array[$i]];
	}
}
return $display;
}

/*
* узнать опции на вингах
* @exnum - цифра эксел
* @n - номер опции в массиве wing_opt
* @n1 - номер опции в массиве wing_opt
* @show_info - остатки опции на вывод
*/
function knowWopt($exnum,$n,$n1,$show_info, $sum,$mnoz)
{
 $ret_arr = array();
/*wings options add*/
 $wing_opt[0]="Additional wizardy dmg";
 $wing_opt[1]="Additional dmg";
 $wing_opt[2]="Automatic HP recovery";
 $wing_opt[3]="Additional defence";

 if (($exnum>=31 and $exnum<=64 and $exnum!=32)  or $exnum==0)
 {
  $ret_arr[1] = $wing_opt[$n];
  if ($exnum<=31) $ret_arr[2] = $show_info; 
  else 
  {
   $ret_arr[2] = ($show_info+4);
   $exnum-=64;
  }
 }
 elseif($exnum>=96 or $exnum==32)
 {
  if ($exnum>=96)
  {
   $ret_arr[2] =($show_info*$mnoz)+$sum;
   $exnum-=96;
  }
  else
  {
   $ret_arr[2] =($show_info*$mnoz);
   $exnum-=32;
  }
  $ret_arr[1] = $wing_opt[$n1];
 }
 $ret_arr[0] = $exnum;
 return $ret_arr;
}
?>