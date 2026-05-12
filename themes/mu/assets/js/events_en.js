/**
 * @package ExillumCMS
 * @author NexT
 * @link http://exengine.ru
 */
 
var Events = {};

Events.text = [
	['Starts in', '<font color="green">Open, to start</font>'],
	['Will appear in', '<font color="red">Hurry up, left</font>']
];

Events.sked = [
	[' - Blood Castle', 0, '00:30', '02:30', '04:30', '06:30', '08:30', '10:30', '12:30', '14:30', '16:30', '18:30', '20:30', '22:30'],
	[' - Devil Square', 0, '00:00', '02:00', '04:00', '06:00', '04:00', '06:30', '08:00', '10:00', '12:00', '14:00', '16:00', '18:00', '20:00', '22:00'],
	[' - Chaos Castle', 0, '01:00', '03:00', '05:00', '07:00', '09:00', '11:00', '13:00', '15:00', '17:00', '19:00', '21:00', '23:00'],
	[' - White Wizard', 1, '03:30', '06:30', '09:30', '12:30', '15:30', '18:30', '21:30'],
	[' - Golden Invasion', 1, '00:15', '06:15', '12:15', '18:15'],
	[' - Loren Deep', 1, '11:15'],
	[' - HAPPY HOUR', 1, '20:00'],
];

Events.init = function(e) 
{
	if (typeof e == "string") var g = new Date(new Date().toString().replace(/\d+:\d+:\d+/g, e));
	
	var f = (typeof e == "number" ? e : (g.getHours() * 60 + g.getMinutes()) * 60 + g.getSeconds()),
		q = Events.sked,
		j = [];
		
	for (var a = 0; a < q.length; a++)
	{
		var n = q[a];
		for (var k = 2; k < q[a].length; k++)
		{
			var b = 0,
				p = q[a][k].split(":"),
				o = (p[0] * 60 + p[1] * 1) * 60,
				c = q[a][2].split(":");
				
			if (q[a].length - 1 == k && (o - f) < 0) b = 1;
			
			var r = b ? (1440 * 60 - f) + ((c[0] * 60 + c[1] * 1) * 60) : o - f;
			
			if (f <= o || b) 
			{
				var l = Math.floor((r / 60) / 60),
					l = l < 10 ? "0" + l : l,
					d = Math.floor((r / 60) % 60),
					d = d < 10 ? "0" + d : d,
					u = r % 60,
					u = u < 10 ? "0" + u : u;
				j.push('<div class="event">' + '<b class="time">' + q[a][b ? 2 : k] + "</b><b>" + n[0] + '</b><div class="small"><span>' + (l + ":" + d + ":" + u) + "</span>" + (Events.text[q[a][1]][+(l == 0 && d < (q[a][1] ? 1 : 5))]) + "</div></div>");
				break;
			};
		};
	};
	document.getElementById("events").innerHTML = j.join("");
	
	setTimeout(function() 
	{
		Events.init(f == 86400 ? 1 : ++f);
	}, 1000);
};

jQuery(document).ready(function($) 
{
	var d = new Date();
	hh = d.getUTCHours() + 3, mm = d.getMinutes(), ss = d.getSeconds();
	
	if (hh >= 24) 
	{
		hh = hh - 24;
	}
	
	if (hh <= 9) 
	{
		hh = "0" + hh;
	}
	
	if (mm <= 9) 
	{
		mm = "0" + mm;
	}
	
	if (ss <= 9) 
	{
		ss = "0" + ss;
	}
	
	Events.init(hh + ":" + mm + ":" + ss);
});