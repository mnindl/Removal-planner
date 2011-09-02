/*global window */
/*global $ */
/*global alert */
/*global googl */
google.load("jquery", "1.6.2");
google.load("jqueryui", "1.8.14");
if (!window.UMZUGSPLANER) { var UMZUGSPLANER = {}; }

UMZUGSPLANER.compute = (function () {
  var currentTab,
      removal_date,
      removal_type,
      day_milli_sec = 24 * 60 * 60 * 1000,
      week_milli_sec = 7 * 24 * 60 * 60 * 1000,
      dayNames = ['Sonntag', 'Montag', 'Dienstag', 'Mittwoch', 'Donnerstag', 'Freitag', 'Samstag'];
  function createPDF() {
    var time = (Math.round((removal_date.getTime()) / 1000)),
        url = "http://pdf.umzugskalender.de/umzugsplaner/generatePDF/generate_pdf_small_font.php?currentTab="+currentTab+"&removal_date="+time+"&removal_type="+removal_type;
    window.open(url);
  }
  function getTip(xml, order, removal_type) {
    var tip,
        headline,
        flow_text,
        list,
        list_items,
        links,
        $tip = $(xml).find('removalTipItem[type="'+removal_type+'"][order="'+order+'"]');
    if ($tip.size() < 1) {
      $tip = $(xml).find('removalTipItem[type="common"][order="'+order+'"]');
    }
    tip = "<div class=\"tip_body\">";
    headline = $tip.find('headline').text();
    tip += "<h3 class=\"headline\">"+headline+"</h3>";
    flow_text = $tip.find('text');
    list = $tip.find('list');
    if (flow_text.size() >= 1) {
      tip += "<p class=\"text\">"+flow_text.text()+"</p>";
    }
    if (list.size() >= 1) {
      tip += "<ul class=\"list\">";
      list_items = list.find('listItem');
      list_items.each(function(){
        tip += "<li class=\"list_item\">"+$(this).text()+"</li>";
      });
      tip += "</ul>";
    }
    tip += "</div>";
    links = $tip.find('link');
    if (links.size() >= 1) {
      tip += "<div class=\"link_list\">";
      links.each(function(){
        var $this = $(this);
        tip += "<a class=\""+$this.attr('type')+"\"href=\""+$this.attr('href')+"\" target=\"_blank\">"+$this.text()+"</a>";
      });
      tip += "</div>"
    }
    return tip;
  }
  function dateOut(item_date) {
    var temp,
        day,
        month,
        item_date_format;
    if (item_date.getTime() < new Date().getTime() - day_milli_sec) {
      temp = "<p class=\"late_day\">baldm&#246;glichst</p>";
    } else {
      day =  item_date.getDate();
      month = Number(item_date.getMonth()) +1;
      item_date_format = (day > 9 ? day : "0"+day)+"."+(month > 9 ? month : "0"+month)+"."+item_date.getFullYear();
      temp = "<p class=\"day\">"+dayNames[item_date.getDay()]+"</p><p>"+item_date_format+"</p>";
    }
    return temp;
  }  
  function setTabData (xml) {
    removal_type = $('input:radio[name="removal_type"]:checked').val();
    removal_date = $( "#datepicker" ).datepicker("getDate" );
    var common_items = $(xml).find('removalTipItem[type="common"]'),
        removal_type_items = $(xml).find('removalTipItem[type="'+removal_type+'"]'),
        removal_type_items_time = [],
        common_items_time = [],
        merged_items_time,
        n,
        i2 = -1,
        i3 = -1,
        i4 = -1,
        html_tab1 = [],
        html_tab2 = [],
        html_tab3 = [],
        html_tab4 = [],
        i;
    for (i = -1, n = removal_type_items.length; ++i < n;) {
      removal_type_items_time[i] = Number($(removal_type_items[i]).attr('order'));
    }
    for (i = -1, n = common_items.length; ++i < n;) {
      common_items_time[i] = Number($(common_items[i]).attr('order'));
    }
    merged_items_time = removal_type_items_time.concat(common_items_time);
    merged_items_time.sort(function(a,b){return a - b});
    for (i = -1, n = merged_items_time.length; ++i < n;) {
      var order = merged_items_time[i], 
          item_date = new Date(removal_date.getTime() + order*day_milli_sec),
          removal_week_end = new Date(removal_date.getTime() + week_milli_sec),
          tip = getTip(xml, order, removal_type),
          item_date_out = dateOut(item_date),
          tab_id = $('li.ui-tabs-selected a').attr('href');
      html_tab1[i] = "<div class=\"tip_holder\">"
                     +"<div class=\"time\">"+item_date_out+"</div>"
                     +"<div class=\"tip\">"+tip+"</div>"
                     +"</div>";
      if (item_date < removal_date) {
        ++i2;
        html_tab2[i2] = html_tab1[i];
      }
      if (item_date >= removal_date && item_date <= removal_week_end) {
        ++i3;
        html_tab3[i3] = html_tab1[i];
      }
      if (item_date > removal_week_end) {
        ++i4;
        html_tab4[i4] = html_tab1[i];
      }
    }
    $('.scroll-pane .tips').html("");
    $('#tab1 .scroll-pane .tips').append(html_tab1.join(""));
    $('#tab2 .scroll-pane .tips').append(html_tab2.join(""));
    $('#tab3 .scroll-pane .tips').append(html_tab3.join(""));
    $('#tab4 .scroll-pane .tips').append(html_tab4.join(""));
    $('#plan_result').fadeIn('slow');
    window.setTimeout(function(tab_id) {
      $('.loading_animation').fadeOut('slow');
      $('.tab_content').fadeIn('slow');
      if ($('li.ui-tabs-selected a').attr('href') !== undefined) {
        $(tab_id+" .scroll-pane").jScrollPane();
      }
    }, 1000);
  }
  function initTabs() {
    $('#tabs').tabs( {
      show: function(ev, ui) {
        currentTab = ui.panel.id;
        $("#"+ui.panel.id+" .scroll-pane").jScrollPane();
      }
    });
  }
  function getXml() {
    $.ajax({
      type: "GET",
      url: "removalTips.xml",
      dataType: "xml",
      async: true,
      success: function (xml) {
        setTabData(xml);
        initTabs();
      },
      error: function (req, error, exception) {
        alert("Die Tips konnten leider nicht geladen werden Versuchen Sie es bitte sp&#228;ter nochmal.");
      }
    });
  }  
  function initDatePicker() {
    $.datepicker.regional.de = {
    monthNames: ['Januar','Februar','M&#228;rz','April','Mai','Juni',
    'Juli','August','September','Oktober','November','Dezember'],
    monthNamesShort: ['Jan','Feb','Mär','Apr','Mai','Jun',
    'Jul','Aug','Sep','Okt','Nov','Dez'],
    dayNames: dayNames,
    dayNamesShort: ['So','Mo','Di','Mi','Do','Fr','Sa'],
    dayNamesMin: ['So','Mo','Di','Mi','Do','Fr','Sa'],
    weekHeader: 'Wo',
    dateFormat: 'dd.mm.yy',
    firstDay: 1,
    showMonthAfterYear: true,
    showAnim: 'fold',
    beforeShow: function(input, inst) {
        inst.dpDiv.css({marginTop: (-input.offsetHeight-2) + 'px'});
    }};
    $.datepicker.setDefaults($.datepicker.regional.de);
    $('#datepicker').datepicker();
    $('#datepicker').datepicker("setDate", new Date() );
    getXml();
  }
  function initEventshandler() {
    $("#rem_plann_form .submit").click(function (event) {
      event.preventDefault();
      getXml();
      $('#start_layer').fadeOut('slow');
      $(".tab_content").fadeOut('slow');
      $(".loading_animation").fadeIn('slow');
    });
    $('#form_label').click(function(){
      $('#datepicker').datepicker("show");
    });
    $('.pdf_export').click(function() {
      createPDF();
    });
  }
  function init() {
    initDatePicker();
    initEventshandler();
  }
  return {
    init: function () {
      return init();
    }
  };
}());
google.setOnLoadCallback(UMZUGSPLANER.compute.init);
