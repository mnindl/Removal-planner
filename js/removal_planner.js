/*global window */
/*global $ */
/*global alert */
google.load("jquery", "1.6.2");
google.load("jqueryui", "1.8.14");
if (!window.UMZUGSPLANER) { var UMZUGSPLANER = {}; }

UMZUGSPLANER.compute = (function () {
  var pdfTab = [],
      html_tab1 = [],
      html_tab2 = [],
      html_tab3 = [],
      html_tab4 = [];
  function createPDF() {
    var doc = new jsPDF(),
        pdf_data,
        n,
        pdf_row = 40;
    doc.setFontSize(22);
    doc.text(20, 20, "Umzugsplan by ImmoScout24");
    doc.setFontSize(16);
    doc.text(20, 30, 'Datum');
    // test
    doc.drawLine(100, 100, 100, 120, 1.0, 'dashed');
    doc.text(80, 30, 'Aufgabe');
    doc.setFontSize(14);
    switch(pdfTab)
    {
    case "html_tab1":
      pdf_data = html_tab1;
      break;
    case "html_tab2":
      pdf_data = html_tab2;
      break;
    case "html_tab3":
      pdf_data = html_tab3;
      break;
    case "html_tab4":
      pdf_data = html_tab4;
      break;
    }
    for (var i = -1, n = pdf_data.length; ++i < n;) {
      var time = pdf_data[i].slice(pdf_data[i].indexOf('time') + 6, pdf_data[i].indexOf('<\/div>'));
      var tip = pdf_data[i].slice(pdf_data[i].indexOf('tip') + 5, pdf_data[i].lastIndexOf('<\/div>'));
      doc.text(20, pdf_row, time);
      doc.text(80, pdf_row, tip);
      pdf_row += 10;
      if (pdf_row === 250) {
        doc.addPage();
        pdf_row = 20;
      }
    }
    // Output as Data URI
    doc.output('datauri');
  }
  function initDatePicker() {
    $.datepicker.regional['de'] = {
    closeText: 'schließen',
    prevText: '&#x3c;zurück',
    nextText: 'Vor&#x3e;',
    currentText: 'heute',
    monthNames: ['Januar','Februar','März','April','Mai','Juni',
    'Juli','August','September','Oktober','November','Dezember'],
    monthNamesShort: ['Jan','Feb','Mär','Apr','Mai','Jun',
    'Jul','Aug','Sep','Okt','Nov','Dez'],
    dayNames: ['Sonntag','Montag','Dienstag','Mittwoch','Donnerstag','Freitag','Samstag'],
    dayNamesShort: ['So','Mo','Di','Mi','Do','Fr','Sa'],
    dayNamesMin: ['So','Mo','Di','Mi','Do','Fr','Sa'],
    weekHeader: 'Wo',
    dateFormat: 'dd.mm.yy',
    firstDay: 1,
    isRTL: false,
    showMonthAfterYear: false,
    yearSuffix: ''};
    $.datepicker.setDefaults($.datepicker.regional['de']);
    $('#datepicker').datepicker({ minDate: new Date() });
    $('#datepicker').datepicker("setDate", new Date($("#datepicker").val()) );
  }
  function getTip(xml, order, removal_type) {
    var tip,
        headline,
        $tip = $(xml).find('removalTipItem[type="'+removal_type+'"][order="'+order+'"]');
    if ($tip.size() < 1) {
      $tip = $(xml).find('removalTipItem[type="common"][order="'+order+'"]');
    }
    headline = $tip.find('headline').text();
    tip = "<h3 class=\"headline\">"+headline+"</h3>";
    if ($text = $tip.find('text'), $text.size() >= 1) {
      tip += "<p class=\"text\">"+$text.text()+"</p>";
    }
    if ($list = $tip.find('list'), $list.size() >=1) {
      tip += "<ul class=\"list\">";
      $list_items = $list.find('listItem');
      $list_items.each(function(){
        tip += "<li class=\"list_item\">"+$(this).text()+"</li>";
      });
      tip += "</ul>";
    }
    if ($links = $tip.find('link'), $links.size() >= 1) {
      tip += "<div class=\"link_list\">";
      $links.each(function(){
        var $this = $(this);
        tip += "<a class=\""+$this.attr('type')+"\"href=\""+$this.attr('href')+"\">"
                +$this.text()
                +"</a>";
      });
      tip += "</div>"
    }
    
    return tip;
  }
  function setTabData (xml, removal_type, removal_date) {
    var common_items = $(xml).find('removalTipItem[type="common"]'),
        removal_type_items = $(xml).find('removalTipItem[type="'+removal_type+'"]'),
        removal_type_items_time = [],
        common_items_time = [],
        merged_items_time,
        merged_items_time_size,
        day_milli_sec = 24*60*60*1000,
        week_milli_sec = 7*24*60*60*1000,
        n,
        i2 = -1,
        i3 = -1,
        i4 = -1;
    for (var i = -1, n = removal_type_items.length; ++i < n;) {
      removal_type_items_time[i] = Number($(removal_type_items[i]).attr('order'));
    }
    for (var i = -1, n = common_items.length; ++i < n;) {
      common_items_time[i] = Number($(common_items[i]).attr('order'));
    }
    merged_items_time = removal_type_items_time.concat(common_items_time);
    merged_items_time.sort(function(a,b){return a - b});
    for ( var i = -1, n = merged_items_time.length; ++i < n;) {
     // var start = new Date().getTime();
      var order = merged_items_time[i], 
          item_date = new Date(removal_date.getTime() + order*day_milli_sec),
          removal_week_end = new Date(removal_date.getTime() + week_milli_sec),
          tip = getTip(xml, order, removal_type),
          item_date_out = (item_date.getTime() < new Date().getTime() - day_milli_sec ? "baldm&#246;glichst" : item_date.toLocaleDateString());
    /*var end = new Date().getTime();
    var time = end - start;
    console.log('Execution time: ' + time);*/
      html_tab1[i] = "<div class=\"time\">"+item_date_out+"</div>"
                      +"<div class=\"tip\">"+tip+"</div>";
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
    $('#plan_result').show();
  }
  function initTabs() {
    $('#tabs').tabs( {
      show: function(ev, ui) {
        pdfTab = "html_"+ui.panel.id;
        $("#"+ui.panel.id+" .scroll-pane").jScrollPane();
      }
    });
  }  
  function initEventshandler() {
    $('#rem_plann_form').submit(function(event) {
      event.preventDefault();
      var removal_type = $('input:radio[name="removal_type"]:checked').val();
      var datepicker_date = $( "#datepicker" ).datepicker("getDate" );
      if(datepicker_date !== null) {
        $.ajax({
          type: "GET",
          url: "removalTips.xml",
          dataType: "xml",
          async: true,
          success: function (xml) {
            setTabData(xml, removal_type, datepicker_date);
            initTabs();
          },
          error: function (req, error, exception) {
            alert(eror);
          }
        });
      }
      return false;
    });
    $('#pdf_link').click(function(event) {
      event.preventDefault();
      createPDF();
    });
    $('#print_link').click(function(event) {
      event.preventDefault();
      self.print();
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
