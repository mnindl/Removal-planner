/*global window */
/*global $ */
/*global alert */
google.load("jquery", "1.6.2");
google.load("jqueryui", "1.8.14");
if (!window.UMZUGSPLANER) { var UMZUGSPLANER = {}; }

UMZUGSPLANER.compute = (function () {
  var pdfTab = [];
  function createPDF() {
    var doc = new jsPDF(),
        pdf_data;
   /* switch(pdfTab)
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
    }*/
    //console.log(pdf_data);
    doc.text(20, 20, 'Hello world!');
    doc.text(20, 30, 'This is client-side Javascript, pumping out a PDF.');
    doc.addPage();
    doc.text(20, 20, 'Do you like that?');
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
    $( "#datepicker" ).datepicker();
  }
  function setTabData (xml, removal_type) {
    var html_tab1 = [],
      html_tab2 = [],
      html_tab3 = [],
      html_tab4 = [];
      console.log(html_tab1.length);
    var removal_date = $( "#datepicker" ).datepicker("getDate" ),
        common_items = $(xml).find('removalTipItem[type="common"]'),
        removal_type_items = $(xml).find('removalTipItem[type="'+removal_type+'"]'),
        removal_type_items_time = [],
        common_items_time = [],
        merged_items_time,
        merged_items_time_size,
        removal_type_items_size = removal_type_items.size(),
        common_items_size = common_items.size();
    $('.scroll-pane').html("test");
    for (var i = 0; i < removal_type_items_size; ++i) {
      removal_type_items_time[i] = Number($(removal_type_items[i]).attr('order'));
    }
    for (var i = 0; i < common_items_size; ++i) {
      common_items_time[i] = Number($(common_items[i]).attr('order'));
    }
    merged_items_time = removal_type_items_time.concat(common_items_time);
    merged_items_time.sort(function(a,b){return a - b});
    merged_items_time_size = merged_items_time.length;
    for ( var i = 0; i < merged_items_time_size; ++i) {
      var item_date = new Date(),
          removal_week_end = new Date(),
          order = merged_items_time[i],
          tip = $(xml).find('removalTipItem[type="'+removal_type+'"][order="'+order+'"] headline');
      item_date.setDate(removal_date.getDate()+(order));
      if (tip.size() >= 1) {
        tip = tip.text();
      } else {
        tip = $(xml).find('removalTipItem[type="common"][order="'+order+'"] headline').text();
      }
      html_tab1[i] = "<div class=\"time\">"+item_date+i+"</div>"
                      +"<div class=\"tip\">"+tip+"</div><br/><br/>";
      if (item_date < removal_date) {
        html_tab2[i] = html_tab1[i];
      }
      removal_week_end.setDate(removal_date.getDate()+ 7);
      if (item_date >= removal_date && item_date <= removal_week_end) {
        html_tab3[i] = html_tab1[i];
      }
      if (item_date > removal_week_end) {
        html_tab4[i] = html_tab1[i];
      }
    }
    console.log(html_tab1.length);
    $('#tab1 .scroll-pane').append(html_tab1.join(""));
    $('#tab2 .scroll-pane').append(html_tab2.join(""));
    $('#tab3 .scroll-pane').append(html_tab3.join(""));
    $('#tab4 .scroll-pane').append(html_tab4.join(""));
    $('#plan_result').show();
  }
  function initEventshandler() {
    $('#rem_plann_form').submit(function() {
      var removal_type = $('input:radio[name="removal_type"]:checked').val();
      $.ajax({
        type: "GET",
        url: "items.xml",
        dataType: "xml",
        async: true,
        success: function (xml) {
          setTabData(xml, removal_type);
        },
        error: function (req, error, exception) {
          alert(eror);
        }
      });
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
    $('#tabs').tabs( {
      show: function(ev, ui) {
        pdfTab = "html_"+ui.panel.id;
      }
    });
     /*$('.scroll-pane').jScrollPane();*/
  }
  return {
    init: function () {
      return init();
    }
  };
}());
google.setOnLoadCallback(UMZUGSPLANER.compute.init);
