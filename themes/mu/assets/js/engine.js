function engine(event){if(event.altKey && event.keyCode == 65) alert('EX Engine v2.1\n\nПрограммист: NexT\nSkype: androjd123\n\nСервер: #id19');}
$(document).ready(function() {				   
	$('#lang_en').click(function() {						 
		document.cookie='lang=en' + '; path=/; expires=Mon, 01-Jan-2050 00:00:00 GMT';
		window.location.reload();
	});
	
	$('#lang_ru').click(function() {
		document.cookie='lang=ru' + '; path=/; expires=Mon, 01-Jan-2050 00:00:00 GMT';
		window.location.reload();
	});

	$('select[id=selectServer]').each(function(){ 
		$(this).change(function(){ 
		 	document.cookie='server=' + $(this).val() + '; path=/; expires=Mon, 01-Jan-2050 00:00:00 GMT';
		 	window.location.reload();
		});
	});
	
	var t;
 	$('#navi li').hover(function(e) {
		clearTimeout(t);
        var y = this.offsetTop-5;
		$('.arrow').stop().animate({top: y}, 300);
    },function(){
		t = setTimeout(function(){
			$('.arrow').stop().animate({top: -5},300);
		},500);
		});
});
function resetTF() {
    var f = document.tf;
    f.category.selectedIndex = 0;
	f.excellent.checked = false;
	f.ancient.checked = false;
	f.pvp.checked = false;
	f.harmony.checked = false;
	f.skill.checked = false;
	f.luck.checked = false;
	f.life.checked = false;
    f.min_level.selectedIndex = 0;
    f.max_level.selectedIndex = 15;	
    f.curr.selectedIndex = 0;
    f.min_price.value = '';
    f.max_price.value = '';
}