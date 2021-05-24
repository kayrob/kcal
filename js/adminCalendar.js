/**
 *
 * Timepicker add-on
 */
(function($){$.extend($.ui,{timepicker:{version:"0.9.9"}});function Timepicker(){this.regional=[];this.regional[""]={currentText:"Now",closeText:"Done",ampm:false,amNames:["AM","A"],pmNames:["PM","P"],timeFormat:"hh:mm tt",timeSuffix:"",timeOnlyTitle:"Choose Time",timeText:"Time",hourText:"Hour",minuteText:"Minute",secondText:"Second",millisecText:"Millisecond",timezoneText:"Time Zone"};this._defaults={showButtonPanel:true,timeOnly:false,showHour:true,showMinute:true,showSecond:false,showMillisec:false,showTimezone:false,showTime:true,stepHour:1,stepMinute:1,stepSecond:1,stepMillisec:1,hour:0,minute:0,second:0,millisec:0,timezone:"+0000",hourMin:0,minuteMin:0,secondMin:0,millisecMin:0,hourMax:23,minuteMax:59,secondMax:59,millisecMax:999,minDateTime:null,maxDateTime:null,onSelect:null,hourGrid:0,minuteGrid:0,secondGrid:0,millisecGrid:0,alwaysSetTime:true,separator:" ",altFieldTimeOnly:true,showTimepicker:true,timezoneIso8609:false,timezoneList:null,addSliderAccess:false,sliderAccessArgs:null};$.extend(this._defaults,this.regional[""])}$.extend(Timepicker.prototype,{$input:null,$altInput:null,$timeObj:null,inst:null,hour_slider:null,minute_slider:null,second_slider:null,millisec_slider:null,timezone_select:null,hour:0,minute:0,second:0,millisec:0,timezone:"+0000",hourMinOriginal:null,minuteMinOriginal:null,secondMinOriginal:null,millisecMinOriginal:null,hourMaxOriginal:null,minuteMaxOriginal:null,secondMaxOriginal:null,millisecMaxOriginal:null,ampm:"",formattedDate:"",formattedTime:"",formattedDateTime:"",timezoneList:null,setDefaults:function(settings){extendRemove(this._defaults,settings||{});return this},_newInst:function($input,o){var tp_inst=new Timepicker(),inlineSettings={};for(var attrName in this._defaults){var attrValue=$input.attr("time:"+attrName);if(attrValue){try{inlineSettings[attrName]=eval(attrValue)}catch(err){inlineSettings[attrName]=attrValue}}}tp_inst._defaults=$.extend({},this._defaults,inlineSettings,o,{beforeShow:function(input,dp_inst){if($.isFunction(o.beforeShow)){return o.beforeShow(input,dp_inst,tp_inst)}},onChangeMonthYear:function(year,month,dp_inst){tp_inst._updateDateTime(dp_inst);if($.isFunction(o.onChangeMonthYear)){o.onChangeMonthYear.call($input[0],year,month,dp_inst,tp_inst)}},onClose:function(dateText,dp_inst){if(tp_inst.timeDefined===true&&$input.val()!=""){tp_inst._updateDateTime(dp_inst)}if($.isFunction(o.onClose)){o.onClose.call($input[0],dateText,dp_inst,tp_inst)}},timepicker:tp_inst});tp_inst.amNames=$.map(tp_inst._defaults.amNames,function(val){return val.toUpperCase()});tp_inst.pmNames=$.map(tp_inst._defaults.pmNames,function(val){return val.toUpperCase()});if(tp_inst._defaults.timezoneList===null){var timezoneList=[];for(var i=-11;i<=12;i++){timezoneList.push((i>=0?"+":"-")+("0"+Math.abs(i).toString()).slice(-2)+"00")}if(tp_inst._defaults.timezoneIso8609){timezoneList=$.map(timezoneList,function(val){return val=="+0000"?"Z":(val.substring(0,3)+":"+val.substring(3))})}tp_inst._defaults.timezoneList=timezoneList}tp_inst.hour=tp_inst._defaults.hour;tp_inst.minute=tp_inst._defaults.minute;tp_inst.second=tp_inst._defaults.second;tp_inst.millisec=tp_inst._defaults.millisec;tp_inst.ampm="";tp_inst.$input=$input;if(o.altField){tp_inst.$altInput=$(o.altField).css({cursor:"pointer"}).focus(function(){$input.trigger("focus")})}if(tp_inst._defaults.minDate==0||tp_inst._defaults.minDateTime==0){tp_inst._defaults.minDate=new Date()}if(tp_inst._defaults.maxDate==0||tp_inst._defaults.maxDateTime==0){tp_inst._defaults.maxDate=new Date()}if(tp_inst._defaults.minDate!==undefined&&tp_inst._defaults.minDate instanceof Date){tp_inst._defaults.minDateTime=new Date(tp_inst._defaults.minDate.getTime())}if(tp_inst._defaults.minDateTime!==undefined&&tp_inst._defaults.minDateTime instanceof Date){tp_inst._defaults.minDate=new Date(tp_inst._defaults.minDateTime.getTime())}if(tp_inst._defaults.maxDate!==undefined&&tp_inst._defaults.maxDate instanceof Date){tp_inst._defaults.maxDateTime=new Date(tp_inst._defaults.maxDate.getTime())}if(tp_inst._defaults.maxDateTime!==undefined&&tp_inst._defaults.maxDateTime instanceof Date){tp_inst._defaults.maxDate=new Date(tp_inst._defaults.maxDateTime.getTime())}return tp_inst},_addTimePicker:function(dp_inst){var currDT=(this.$altInput&&this._defaults.altFieldTimeOnly)?this.$input.val()+" "+this.$altInput.val():this.$input.val();this.timeDefined=this._parseTime(currDT);this._limitMinMaxDateTime(dp_inst,false);this._injectTimePicker()},_parseTime:function(timeString,withDate){var regstr=this._defaults.timeFormat.toString().replace(/h{1,2}/ig,"(\\d?\\d)").replace(/m{1,2}/ig,"(\\d?\\d)").replace(/s{1,2}/ig,"(\\d?\\d)").replace(/l{1}/ig,"(\\d?\\d?\\d)").replace(/t{1,2}/ig,this._getPatternAmpm()).replace(/z{1}/ig,"(z|[-+]\\d\\d:?\\d\\d)?").replace(/\s/g,"\\s?")+this._defaults.timeSuffix+"$",order=this._getFormatPositions(),ampm="",treg;if(!this.inst){this.inst=$.datepicker._getInst(this.$input[0])}if(withDate||!this._defaults.timeOnly){var dp_dateFormat=$.datepicker._get(this.inst,"dateFormat");var specials=new RegExp("[.*+?|()\\[\\]{}\\\\]","g");regstr="^.{"+dp_dateFormat.length+",}?"+this._defaults.separator.replace(specials,"\\$&")+regstr}treg=timeString.match(new RegExp(regstr,"i"));if(treg){if(order.t!==-1){if(treg[order.t]===undefined||treg[order.t].length===0){ampm="";this.ampm=""}else{ampm=$.inArray(treg[order.t].toUpperCase(),this.amNames)!==-1?"AM":"PM";this.ampm=this._defaults[ampm=="AM"?"amNames":"pmNames"][0]}}if(order.h!==-1){if(ampm=="AM"&&treg[order.h]=="12"){this.hour=0}else{if(ampm=="PM"&&treg[order.h]!="12"){this.hour=(parseFloat(treg[order.h])+12).toFixed(0)}else{this.hour=Number(treg[order.h])}}}if(order.m!==-1){this.minute=Number(treg[order.m])}if(order.s!==-1){this.second=Number(treg[order.s])}if(order.l!==-1){this.millisec=Number(treg[order.l])}if(order.z!==-1&&treg[order.z]!==undefined){var tz=treg[order.z].toUpperCase();switch(tz.length){case 1:tz=this._defaults.timezoneIso8609?"Z":"+0000";break;case 5:if(this._defaults.timezoneIso8609){tz=tz.substring(1)=="0000"?"Z":tz.substring(0,3)+":"+tz.substring(3)}break;case 6:if(!this._defaults.timezoneIso8609){tz=tz=="Z"||tz.substring(1)=="00:00"?"+0000":tz.replace(/:/,"")}else{if(tz.substring(1)=="00:00"){tz="Z"}}break}this.timezone=tz}return true}return false},_getPatternAmpm:function(){var markers=[];o=this._defaults;if(o.amNames){$.merge(markers,o.amNames)}if(o.pmNames){$.merge(markers,o.pmNames)}markers=$.map(markers,function(val){return val.replace(/[.*+?|()\[\]{}\\]/g,"\\$&")});return"("+markers.join("|")+")?"},_getFormatPositions:function(){var finds=this._defaults.timeFormat.toLowerCase().match(/(h{1,2}|m{1,2}|s{1,2}|l{1}|t{1,2}|z)/g),orders={h:-1,m:-1,s:-1,l:-1,t:-1,z:-1};if(finds){for(var i=0;i<finds.length;i++){if(orders[finds[i].toString().charAt(0)]==-1){orders[finds[i].toString().charAt(0)]=i+1}}}return orders},_injectTimePicker:function(){var $dp=this.inst.dpDiv,o=this._defaults,tp_inst=this,hourMax=parseInt((o.hourMax-((o.hourMax-o.hourMin)%o.stepHour)),10),minMax=parseInt((o.minuteMax-((o.minuteMax-o.minuteMin)%o.stepMinute)),10),secMax=parseInt((o.secondMax-((o.secondMax-o.secondMin)%o.stepSecond)),10),millisecMax=parseInt((o.millisecMax-((o.millisecMax-o.millisecMin)%o.stepMillisec)),10),dp_id=this.inst.id.toString().replace(/([^A-Za-z0-9_])/g,"");if($dp.find("div#ui-timepicker-div-"+dp_id).length===0&&o.showTimepicker){var noDisplay=' style="display:none;"',html='<div class="ui-timepicker-div" id="ui-timepicker-div-'+dp_id+'"><dl><dt class="ui_tpicker_time_label" id="ui_tpicker_time_label_'+dp_id+'"'+((o.showTime)?"":noDisplay)+">"+o.timeText+'</dt><dd class="ui_tpicker_time" id="ui_tpicker_time_'+dp_id+'"'+((o.showTime)?"":noDisplay)+'></dd><dt class="ui_tpicker_hour_label" id="ui_tpicker_hour_label_'+dp_id+'"'+((o.showHour)?"":noDisplay)+">"+o.hourText+"</dt>",hourGridSize=0,minuteGridSize=0,secondGridSize=0,millisecGridSize=0,size;html+='<dd class="ui_tpicker_hour"><div id="ui_tpicker_hour_'+dp_id+'"'+((o.showHour)?"":noDisplay)+"></div>";if(o.showHour&&o.hourGrid>0){html+='<div style="padding-left: 1px"><table class="ui-tpicker-grid-label"><tr>';for(var h=o.hourMin;h<=hourMax;h+=parseInt(o.hourGrid,10)){hourGridSize++;var tmph=(o.ampm&&h>12)?h-12:h;if(tmph<10){tmph="0"+tmph}if(o.ampm){if(h==0){tmph=12+"a"}else{if(h<12){tmph+="a"}else{tmph+="p"}}}html+="<td>"+tmph+"</td>"}html+="</tr></table></div>"}html+="</dd>";html+='<dt class="ui_tpicker_minute_label" id="ui_tpicker_minute_label_'+dp_id+'"'+((o.showMinute)?"":noDisplay)+">"+o.minuteText+'</dt><dd class="ui_tpicker_minute"><div id="ui_tpicker_minute_'+dp_id+'"'+((o.showMinute)?"":noDisplay)+"></div>";if(o.showMinute&&o.minuteGrid>0){html+='<div style="padding-left: 1px"><table class="ui-tpicker-grid-label"><tr>';for(var m=o.minuteMin;m<=minMax;m+=parseInt(o.minuteGrid,10)){minuteGridSize++;html+="<td>"+((m<10)?"0":"")+m+"</td>"}html+="</tr></table></div>"}html+="</dd>";html+='<dt class="ui_tpicker_second_label" id="ui_tpicker_second_label_'+dp_id+'"'+((o.showSecond)?"":noDisplay)+">"+o.secondText+'</dt><dd class="ui_tpicker_second"><div id="ui_tpicker_second_'+dp_id+'"'+((o.showSecond)?"":noDisplay)+"></div>";if(o.showSecond&&o.secondGrid>0){html+='<div style="padding-left: 1px"><table><tr>';for(var s=o.secondMin;s<=secMax;s+=parseInt(o.secondGrid,10)){secondGridSize++;html+="<td>"+((s<10)?"0":"")+s+"</td>"}html+="</tr></table></div>"}html+="</dd>";html+='<dt class="ui_tpicker_millisec_label" id="ui_tpicker_millisec_label_'+dp_id+'"'+((o.showMillisec)?"":noDisplay)+">"+o.millisecText+'</dt><dd class="ui_tpicker_millisec"><div id="ui_tpicker_millisec_'+dp_id+'"'+((o.showMillisec)?"":noDisplay)+"></div>";if(o.showMillisec&&o.millisecGrid>0){html+='<div style="padding-left: 1px"><table><tr>';for(var l=o.millisecMin;l<=millisecMax;l+=parseInt(o.millisecGrid,10)){millisecGridSize++;html+="<td>"+((l<10)?"0":"")+l+"</td>"}html+="</tr></table></div>"}html+="</dd>";html+='<dt class="ui_tpicker_timezone_label" id="ui_tpicker_timezone_label_'+dp_id+'"'+((o.showTimezone)?"":noDisplay)+">"+o.timezoneText+"</dt>";html+='<dd class="ui_tpicker_timezone" id="ui_tpicker_timezone_'+dp_id+'"'+((o.showTimezone)?"":noDisplay)+"></dd>";html+="</dl></div>";$tp=$(html);if(o.timeOnly===true){$tp.prepend('<div class="ui-widget-header ui-helper-clearfix ui-corner-all"><div class="ui-datepicker-title">'+o.timeOnlyTitle+"</div></div>");$dp.find(".ui-datepicker-header, .ui-datepicker-calendar").hide()}this.hour_slider=$tp.find("#ui_tpicker_hour_"+dp_id).slider({orientation:"horizontal",value:this.hour,min:o.hourMin,max:hourMax,step:o.stepHour,slide:function(event,ui){tp_inst.hour_slider.slider("option","value",ui.value);tp_inst._onTimeChange()}});this.minute_slider=$tp.find("#ui_tpicker_minute_"+dp_id).slider({orientation:"horizontal",value:this.minute,min:o.minuteMin,max:minMax,step:o.stepMinute,slide:function(event,ui){tp_inst.minute_slider.slider("option","value",ui.value);tp_inst._onTimeChange()}});this.second_slider=$tp.find("#ui_tpicker_second_"+dp_id).slider({orientation:"horizontal",value:this.second,min:o.secondMin,max:secMax,step:o.stepSecond,slide:function(event,ui){tp_inst.second_slider.slider("option","value",ui.value);tp_inst._onTimeChange()}});this.millisec_slider=$tp.find("#ui_tpicker_millisec_"+dp_id).slider({orientation:"horizontal",value:this.millisec,min:o.millisecMin,max:millisecMax,step:o.stepMillisec,slide:function(event,ui){tp_inst.millisec_slider.slider("option","value",ui.value);tp_inst._onTimeChange()}});this.timezone_select=$tp.find("#ui_tpicker_timezone_"+dp_id).append("<select></select>").find("select");$.fn.append.apply(this.timezone_select,$.map(o.timezoneList,function(val,idx){return $("<option />").val(typeof val=="object"?val.value:val).text(typeof val=="object"?val.label:val)}));this.timezone_select.val((typeof this.timezone!="undefined"&&this.timezone!=null&&this.timezone!="")?this.timezone:o.timezone);this.timezone_select.change(function(){tp_inst._onTimeChange()});if(o.showHour&&o.hourGrid>0){size=100*hourGridSize*o.hourGrid/(hourMax-o.hourMin);$tp.find(".ui_tpicker_hour table").css({width:size+"%",marginLeft:(size/(-2*hourGridSize))+"%",borderCollapse:"collapse"}).find("td").each(function(index){$(this).click(function(){var h=$(this).html();if(o.ampm){var ap=h.substring(2).toLowerCase(),aph=parseInt(h.substring(0,2),10);if(ap=="a"){if(aph==12){h=0}else{h=aph}}else{if(aph==12){h=12}else{h=aph+12}}}tp_inst.hour_slider.slider("option","value",h);tp_inst._onTimeChange();tp_inst._onSelectHandler()}).css({cursor:"pointer",width:(100/hourGridSize)+"%",textAlign:"center",overflow:"hidden"})})}if(o.showMinute&&o.minuteGrid>0){size=100*minuteGridSize*o.minuteGrid/(minMax-o.minuteMin);$tp.find(".ui_tpicker_minute table").css({width:size+"%",marginLeft:(size/(-2*minuteGridSize))+"%",borderCollapse:"collapse"}).find("td").each(function(index){$(this).click(function(){tp_inst.minute_slider.slider("option","value",$(this).html());tp_inst._onTimeChange();tp_inst._onSelectHandler()}).css({cursor:"pointer",width:(100/minuteGridSize)+"%",textAlign:"center",overflow:"hidden"})})}if(o.showSecond&&o.secondGrid>0){$tp.find(".ui_tpicker_second table").css({width:size+"%",marginLeft:(size/(-2*secondGridSize))+"%",borderCollapse:"collapse"}).find("td").each(function(index){$(this).click(function(){tp_inst.second_slider.slider("option","value",$(this).html());tp_inst._onTimeChange();tp_inst._onSelectHandler()}).css({cursor:"pointer",width:(100/secondGridSize)+"%",textAlign:"center",overflow:"hidden"})})}if(o.showMillisec&&o.millisecGrid>0){$tp.find(".ui_tpicker_millisec table").css({width:size+"%",marginLeft:(size/(-2*millisecGridSize))+"%",borderCollapse:"collapse"}).find("td").each(function(index){$(this).click(function(){tp_inst.millisec_slider.slider("option","value",$(this).html());tp_inst._onTimeChange();tp_inst._onSelectHandler()}).css({cursor:"pointer",width:(100/millisecGridSize)+"%",textAlign:"center",overflow:"hidden"})})}var $buttonPanel=$dp.find(".ui-datepicker-buttonpane");if($buttonPanel.length){$buttonPanel.before($tp)}else{$dp.append($tp)}this.$timeObj=$tp.find("#ui_tpicker_time_"+dp_id);if(this.inst!==null){var timeDefined=this.timeDefined;this._onTimeChange();this.timeDefined=timeDefined}var onSelectDelegate=function(){tp_inst._onSelectHandler()};this.hour_slider.bind("slidestop",onSelectDelegate);this.minute_slider.bind("slidestop",onSelectDelegate);this.second_slider.bind("slidestop",onSelectDelegate);this.millisec_slider.bind("slidestop",onSelectDelegate);if(this._defaults.addSliderAccess){var sliderAccessArgs=this._defaults.sliderAccessArgs;setTimeout(function(){if($tp.find(".ui-slider-access").length==0){$tp.find(".ui-slider:visible").sliderAccess(sliderAccessArgs);var sliderAccessWidth=$tp.find(".ui-slider-access:eq(0)").outerWidth(true);if(sliderAccessWidth){$tp.find("table:visible").each(function(){var $g=$(this),oldWidth=$g.outerWidth(),oldMarginLeft=$g.css("marginLeft").toString().replace("%",""),newWidth=oldWidth-sliderAccessWidth,newMarginLeft=((oldMarginLeft*newWidth)/oldWidth)+"%";$g.css({width:newWidth,marginLeft:newMarginLeft})})}}},0)}}},_limitMinMaxDateTime:function(dp_inst,adjustSliders){var o=this._defaults,dp_date=new Date(dp_inst.selectedYear,dp_inst.selectedMonth,dp_inst.selectedDay);if(!this._defaults.showTimepicker){return}if($.datepicker._get(dp_inst,"minDateTime")!==null&&$.datepicker._get(dp_inst,"minDateTime")!==undefined&&dp_date){var minDateTime=$.datepicker._get(dp_inst,"minDateTime"),minDateTimeDate=new Date(minDateTime.getFullYear(),minDateTime.getMonth(),minDateTime.getDate(),0,0,0,0);if(this.hourMinOriginal===null||this.minuteMinOriginal===null||this.secondMinOriginal===null||this.millisecMinOriginal===null){this.hourMinOriginal=o.hourMin;this.minuteMinOriginal=o.minuteMin;this.secondMinOriginal=o.secondMin;this.millisecMinOriginal=o.millisecMin}if(dp_inst.settings.timeOnly||minDateTimeDate.getTime()==dp_date.getTime()){this._defaults.hourMin=minDateTime.getHours();if(this.hour<=this._defaults.hourMin){this.hour=this._defaults.hourMin;this._defaults.minuteMin=minDateTime.getMinutes();if(this.minute<=this._defaults.minuteMin){this.minute=this._defaults.minuteMin;this._defaults.secondMin=minDateTime.getSeconds()}else{if(this.second<=this._defaults.secondMin){this.second=this._defaults.secondMin;this._defaults.millisecMin=minDateTime.getMilliseconds()}else{if(this.millisec<this._defaults.millisecMin){this.millisec=this._defaults.millisecMin}this._defaults.millisecMin=this.millisecMinOriginal}}}else{this._defaults.minuteMin=this.minuteMinOriginal;this._defaults.secondMin=this.secondMinOriginal;this._defaults.millisecMin=this.millisecMinOriginal}}else{this._defaults.hourMin=this.hourMinOriginal;this._defaults.minuteMin=this.minuteMinOriginal;this._defaults.secondMin=this.secondMinOriginal;this._defaults.millisecMin=this.millisecMinOriginal}}if($.datepicker._get(dp_inst,"maxDateTime")!==null&&$.datepicker._get(dp_inst,"maxDateTime")!==undefined&&dp_date){var maxDateTime=$.datepicker._get(dp_inst,"maxDateTime"),maxDateTimeDate=new Date(maxDateTime.getFullYear(),maxDateTime.getMonth(),maxDateTime.getDate(),0,0,0,0);if(this.hourMaxOriginal===null||this.minuteMaxOriginal===null||this.secondMaxOriginal===null){this.hourMaxOriginal=o.hourMax;this.minuteMaxOriginal=o.minuteMax;this.secondMaxOriginal=o.secondMax;this.millisecMaxOriginal=o.millisecMax}if(dp_inst.settings.timeOnly||maxDateTimeDate.getTime()==dp_date.getTime()){this._defaults.hourMax=maxDateTime.getHours();if(this.hour>=this._defaults.hourMax){this.hour=this._defaults.hourMax;this._defaults.minuteMax=maxDateTime.getMinutes();if(this.minute>=this._defaults.minuteMax){this.minute=this._defaults.minuteMax;this._defaults.secondMax=maxDateTime.getSeconds()}else{if(this.second>=this._defaults.secondMax){this.second=this._defaults.secondMax;this._defaults.millisecMax=maxDateTime.getMilliseconds()}else{if(this.millisec>this._defaults.millisecMax){this.millisec=this._defaults.millisecMax}this._defaults.millisecMax=this.millisecMaxOriginal}}}else{this._defaults.minuteMax=this.minuteMaxOriginal;this._defaults.secondMax=this.secondMaxOriginal;this._defaults.millisecMax=this.millisecMaxOriginal}}else{this._defaults.hourMax=this.hourMaxOriginal;this._defaults.minuteMax=this.minuteMaxOriginal;this._defaults.secondMax=this.secondMaxOriginal;this._defaults.millisecMax=this.millisecMaxOriginal}}if(adjustSliders!==undefined&&adjustSliders===true){var hourMax=parseInt((this._defaults.hourMax-((this._defaults.hourMax-this._defaults.hourMin)%this._defaults.stepHour)),10),minMax=parseInt((this._defaults.minuteMax-((this._defaults.minuteMax-this._defaults.minuteMin)%this._defaults.stepMinute)),10),secMax=parseInt((this._defaults.secondMax-((this._defaults.secondMax-this._defaults.secondMin)%this._defaults.stepSecond)),10),millisecMax=parseInt((this._defaults.millisecMax-((this._defaults.millisecMax-this._defaults.millisecMin)%this._defaults.stepMillisec)),10);if(this.hour_slider){this.hour_slider.slider("option",{min:this._defaults.hourMin,max:hourMax}).slider("value",this.hour)}if(this.minute_slider){this.minute_slider.slider("option",{min:this._defaults.minuteMin,max:minMax}).slider("value",this.minute)}if(this.second_slider){this.second_slider.slider("option",{min:this._defaults.secondMin,max:secMax}).slider("value",this.second)}if(this.millisec_slider){this.millisec_slider.slider("option",{min:this._defaults.millisecMin,max:millisecMax}).slider("value",this.millisec)}}},_onTimeChange:function(){var hour=(this.hour_slider)?this.hour_slider.slider("value"):false,minute=(this.minute_slider)?this.minute_slider.slider("value"):false,second=(this.second_slider)?this.second_slider.slider("value"):false,millisec=(this.millisec_slider)?this.millisec_slider.slider("value"):false,timezone=(this.timezone_select)?this.timezone_select.val():false,o=this._defaults;if(typeof(hour)=="object"){hour=false}if(typeof(minute)=="object"){minute=false}if(typeof(second)=="object"){second=false}if(typeof(millisec)=="object"){millisec=false}if(typeof(timezone)=="object"){timezone=false}if(hour!==false){hour=parseInt(hour,10)}if(minute!==false){minute=parseInt(minute,10)}if(second!==false){second=parseInt(second,10)}if(millisec!==false){millisec=parseInt(millisec,10)}var ampm=o[hour<12?"amNames":"pmNames"][0];var hasChanged=(hour!=this.hour||minute!=this.minute||second!=this.second||millisec!=this.millisec||(this.ampm.length>0&&(hour<12)!=($.inArray(this.ampm.toUpperCase(),this.amNames)!==-1))||timezone!=this.timezone);if(hasChanged){if(hour!==false){this.hour=hour}if(minute!==false){this.minute=minute}if(second!==false){this.second=second}if(millisec!==false){this.millisec=millisec}if(timezone!==false){this.timezone=timezone}if(!this.inst){this.inst=$.datepicker._getInst(this.$input[0])}this._limitMinMaxDateTime(this.inst,true)}if(o.ampm){this.ampm=ampm}this.formattedTime=$.datepicker.formatTime(this._defaults.timeFormat,this,this._defaults);if(this.$timeObj){this.$timeObj.text(this.formattedTime+o.timeSuffix)}this.timeDefined=true;if(hasChanged){this._updateDateTime()}},_onSelectHandler:function(){var onSelect=this._defaults.onSelect;var inputEl=this.$input?this.$input[0]:null;if(onSelect&&inputEl){onSelect.apply(inputEl,[this.formattedDateTime,this])}},_formatTime:function(time,format){time=time||{hour:this.hour,minute:this.minute,second:this.second,millisec:this.millisec,ampm:this.ampm,timezone:this.timezone};var tmptime=(format||this._defaults.timeFormat).toString();tmptime=$.datepicker.formatTime(tmptime,time,this._defaults);if(arguments.length){return tmptime}else{this.formattedTime=tmptime}},_updateDateTime:function(dp_inst){dp_inst=this.inst||dp_inst;var dt=$.datepicker._daylightSavingAdjust(new Date(dp_inst.selectedYear,dp_inst.selectedMonth,dp_inst.selectedDay)),dateFmt=$.datepicker._get(dp_inst,"dateFormat"),formatCfg=$.datepicker._getFormatConfig(dp_inst),timeAvailable=dt!==null&&this.timeDefined;this.formattedDate=$.datepicker.formatDate(dateFmt,(dt===null?new Date():dt),formatCfg);var formattedDateTime=this.formattedDate;if(dp_inst.lastVal!==undefined&&(dp_inst.lastVal.length>0&&this.$input.val().length===0)){return}if(this._defaults.timeOnly===true){formattedDateTime=this.formattedTime}else{if(this._defaults.timeOnly!==true&&(this._defaults.alwaysSetTime||timeAvailable)){formattedDateTime+=this._defaults.separator+this.formattedTime+this._defaults.timeSuffix}}this.formattedDateTime=formattedDateTime;if(!this._defaults.showTimepicker){this.$input.val(this.formattedDate)}else{if(this.$altInput&&this._defaults.altFieldTimeOnly===true){this.$altInput.val(this.formattedTime);this.$input.val(this.formattedDate)}else{if(this.$altInput){this.$altInput.val(formattedDateTime);this.$input.val(formattedDateTime)}else{this.$input.val(formattedDateTime)}}}this.$input.trigger("change")}});$.fn.extend({timepicker:function(o){o=o||{};var tmp_args=arguments;if(typeof o=="object"){tmp_args[0]=$.extend(o,{timeOnly:true})}return $(this).each(function(){$.fn.datetimepicker.apply($(this),tmp_args)})},datetimepicker:function(o){o=o||{};var $input=this,tmp_args=arguments;if(typeof(o)=="string"){if(o=="getDate"){return $.fn.datepicker.apply($(this[0]),tmp_args)}else{return this.each(function(){var $t=$(this);$t.datepicker.apply($t,tmp_args)})}}else{return this.each(function(){var $t=$(this);$t.datepicker($.timepicker._newInst($t,o)._defaults)})}}});$.datepicker.formatTime=function(format,time,options){options=options||{};options=$.extend($.timepicker._defaults,options);time=$.extend({hour:0,minute:0,second:0,millisec:0,timezone:"+0000"},time);var tmptime=format;var ampmName=options.amNames[0];var hour=parseInt(time.hour,10);if(options.ampm){if(hour>11){ampmName=options.pmNames[0];if(hour>12){hour=hour%12}}if(hour===0){hour=12}}tmptime=tmptime.replace(/(?:hh?|mm?|ss?|[tT]{1,2}|[lz])/g,function(match){switch(match.toLowerCase()){case"hh":return("0"+hour).slice(-2);case"h":return hour;case"mm":return("0"+time.minute).slice(-2);case"m":return time.minute;case"ss":return("0"+time.second).slice(-2);case"s":return time.second;case"l":return("00"+time.millisec).slice(-3);case"z":return time.timezone;case"t":case"tt":if(options.ampm){if(match.length==1){ampmName=ampmName.charAt(0)}return match.charAt(0)=="T"?ampmName.toUpperCase():ampmName.toLowerCase()}return""}});tmptime=$.trim(tmptime);return tmptime};$.datepicker._base_selectDate=$.datepicker._selectDate;$.datepicker._selectDate=function(id,dateStr){var inst=this._getInst($(id)[0]),tp_inst=this._get(inst,"timepicker");if(tp_inst){tp_inst._limitMinMaxDateTime(inst,true);inst.inline=inst.stay_open=true;this._base_selectDate(id,dateStr);inst.inline=inst.stay_open=false;this._notifyChange(inst);this._updateDatepicker(inst)}else{this._base_selectDate(id,dateStr)}};$.datepicker._base_updateDatepicker=$.datepicker._updateDatepicker;$.datepicker._updateDatepicker=function(inst){var input=inst.input[0];if($.datepicker._curInst&&$.datepicker._curInst!=inst&&$.datepicker._datepickerShowing&&$.datepicker._lastInput!=input){return}if(typeof(inst.stay_open)!=="boolean"||inst.stay_open===false){this._base_updateDatepicker(inst);var tp_inst=this._get(inst,"timepicker");if(tp_inst){tp_inst._addTimePicker(inst)}}};$.datepicker._base_doKeyPress=$.datepicker._doKeyPress;$.datepicker._doKeyPress=function(event){var inst=$.datepicker._getInst(event.target),tp_inst=$.datepicker._get(inst,"timepicker");if(tp_inst){if($.datepicker._get(inst,"constrainInput")){var ampm=tp_inst._defaults.ampm,dateChars=$.datepicker._possibleChars($.datepicker._get(inst,"dateFormat")),datetimeChars=tp_inst._defaults.timeFormat.toString().replace(/[hms]/g,"").replace(/TT/g,ampm?"APM":"").replace(/Tt/g,ampm?"AaPpMm":"").replace(/tT/g,ampm?"AaPpMm":"").replace(/T/g,ampm?"AP":"").replace(/tt/g,ampm?"apm":"").replace(/t/g,ampm?"ap":"")+" "+tp_inst._defaults.separator+tp_inst._defaults.timeSuffix+(tp_inst._defaults.showTimezone?tp_inst._defaults.timezoneList.join(""):"")+(tp_inst._defaults.amNames.join(""))+(tp_inst._defaults.pmNames.join(""))+dateChars,chr=String.fromCharCode(event.charCode===undefined?event.keyCode:event.charCode);return event.ctrlKey||(chr<" "||!dateChars||datetimeChars.indexOf(chr)>-1)}}return $.datepicker._base_doKeyPress(event)};$.datepicker._base_doKeyUp=$.datepicker._doKeyUp;$.datepicker._doKeyUp=function(event){var inst=$.datepicker._getInst(event.target),tp_inst=$.datepicker._get(inst,"timepicker");if(tp_inst){if(tp_inst._defaults.timeOnly&&(inst.input.val()!=inst.lastVal)){try{$.datepicker._updateDatepicker(inst)}catch(err){$.datepicker.log(err)}}}return $.datepicker._base_doKeyUp(event)};$.datepicker._base_gotoToday=$.datepicker._gotoToday;$.datepicker._gotoToday=function(id){var inst=this._getInst($(id)[0]),$dp=inst.dpDiv;this._base_gotoToday(id);var now=new Date();var tp_inst=this._get(inst,"timepicker");if(tp_inst&&tp_inst._defaults.showTimezone&&tp_inst.timezone_select){var tzoffset=now.getTimezoneOffset();var tzsign=tzoffset>0?"-":"+";tzoffset=Math.abs(tzoffset);var tzmin=tzoffset%60;tzoffset=tzsign+("0"+(tzoffset-tzmin)/60).slice(-2)+("0"+tzmin).slice(-2);if(tp_inst._defaults.timezoneIso8609){tzoffset=tzoffset.substring(0,3)+":"+tzoffset.substring(3)}tp_inst.timezone_select.val(tzoffset)}this._setTime(inst,now);$(".ui-datepicker-today",$dp).click()};$.datepicker._disableTimepickerDatepicker=function(target,date,withDate){var inst=this._getInst(target),tp_inst=this._get(inst,"timepicker");$(target).datepicker("getDate");if(tp_inst){tp_inst._defaults.showTimepicker=false;tp_inst._updateDateTime(inst)}};$.datepicker._enableTimepickerDatepicker=function(target,date,withDate){var inst=this._getInst(target),tp_inst=this._get(inst,"timepicker");$(target).datepicker("getDate");if(tp_inst){tp_inst._defaults.showTimepicker=true;tp_inst._addTimePicker(inst);tp_inst._updateDateTime(inst)}};$.datepicker._setTime=function(inst,date){var tp_inst=this._get(inst,"timepicker");if(tp_inst){var defaults=tp_inst._defaults,hour=date?date.getHours():defaults.hour,minute=date?date.getMinutes():defaults.minute,second=date?date.getSeconds():defaults.second,millisec=date?date.getMilliseconds():defaults.millisec;if((hour<defaults.hourMin||hour>defaults.hourMax)||(minute<defaults.minuteMin||minute>defaults.minuteMax)||(second<defaults.secondMin||second>defaults.secondMax)||(millisec<defaults.millisecMin||millisec>defaults.millisecMax)){hour=defaults.hourMin;minute=defaults.minuteMin;second=defaults.secondMin;millisec=defaults.millisecMin}tp_inst.hour=hour;tp_inst.minute=minute;tp_inst.second=second;tp_inst.millisec=millisec;if(tp_inst.hour_slider){tp_inst.hour_slider.slider("value",hour)}if(tp_inst.minute_slider){tp_inst.minute_slider.slider("value",minute)}if(tp_inst.second_slider){tp_inst.second_slider.slider("value",second)}if(tp_inst.millisec_slider){tp_inst.millisec_slider.slider("value",millisec)}tp_inst._onTimeChange();tp_inst._updateDateTime(inst)}};$.datepicker._setTimeDatepicker=function(target,date,withDate){var inst=this._getInst(target),tp_inst=this._get(inst,"timepicker");if(tp_inst){this._setDateFromField(inst);var tp_date;if(date){if(typeof date=="string"){tp_inst._parseTime(date,withDate);tp_date=new Date();tp_date.setHours(tp_inst.hour,tp_inst.minute,tp_inst.second,tp_inst.millisec)}else{tp_date=new Date(date.getTime())}if(tp_date.toString()=="Invalid Date"){tp_date=undefined}this._setTime(inst,tp_date)}}};$.datepicker._base_setDateDatepicker=$.datepicker._setDateDatepicker;$.datepicker._setDateDatepicker=function(target,date){var inst=this._getInst(target),tp_date=(date instanceof Date)?new Date(date.getTime()):date;this._updateDatepicker(inst);this._base_setDateDatepicker.apply(this,arguments);this._setTimeDatepicker(target,tp_date,true)};$.datepicker._base_getDateDatepicker=$.datepicker._getDateDatepicker;$.datepicker._getDateDatepicker=function(target,noDefault){var inst=this._getInst(target),tp_inst=this._get(inst,"timepicker");if(tp_inst){this._setDateFromField(inst,noDefault);var date=this._getDate(inst);if(date&&tp_inst._parseTime($(target).val(),tp_inst.timeOnly)){date.setHours(tp_inst.hour,tp_inst.minute,tp_inst.second,tp_inst.millisec)}return date}return this._base_getDateDatepicker(target,noDefault)};$.datepicker._base_parseDate=$.datepicker.parseDate;$.datepicker.parseDate=function(format,value,settings){var date;try{date=this._base_parseDate(format,value,settings)}catch(err){if(err.indexOf(":")>=0){date=this._base_parseDate(format,value.substring(0,value.length-(err.length-err.indexOf(":")-2)),settings)}else{throw err}}return date};$.datepicker._base_formatDate=$.datepicker._formatDate;$.datepicker._formatDate=function(inst,day,month,year){var tp_inst=this._get(inst,"timepicker");if(tp_inst){if(day){var b=this._base_formatDate(inst,day,month,year)}tp_inst._updateDateTime(inst);return tp_inst.$input.val()}return this._base_formatDate(inst)};$.datepicker._base_optionDatepicker=$.datepicker._optionDatepicker;$.datepicker._optionDatepicker=function(target,name,value){var inst=this._getInst(target),tp_inst=this._get(inst,"timepicker");if(tp_inst){var min,max,onselect;if(typeof name=="string"){if(name==="minDate"||name==="minDateTime"){min=value}else{if(name==="maxDate"||name==="maxDateTime"){max=value}else{if(name==="onSelect"){onselect=value}}}}else{if(typeof name=="object"){if(name.minDate){min=name.minDate}else{if(name.minDateTime){min=name.minDateTime}else{if(name.maxDate){max=name.maxDate}else{if(name.maxDateTime){max=name.maxDateTime}}}}}}if(min){if(min==0){min=new Date()}else{min=new Date(min)}tp_inst._defaults.minDate=min;tp_inst._defaults.minDateTime=min}else{if(max){if(max==0){max=new Date()}else{max=new Date(max)}tp_inst._defaults.maxDate=max;tp_inst._defaults.maxDateTime=max}else{if(onselect){tp_inst._defaults.onSelect=onselect}}}}if(value===undefined){return this._base_optionDatepicker(target,name)}return this._base_optionDatepicker(target,name,value)};function extendRemove(target,props){$.extend(target,props);for(var name in props){if(props[name]===null||props[name]===undefined){target[name]=props[name]}}return target}$.timepicker=new Timepicker();$.timepicker.version="0.9.9"})(jQuery);
/**
* Admin methods for fullCalendar
* This file requires /includes/js/calendar/calendar.js which contains the full calendar
* created by Karen Laansoo November 10, 2010
*/

/**
* Admin methods for fullCalendar
* This file requires /includes/js/calendar/calendar.js which contains the full calendar
* created by Karen Laansoo November 10, 2010
*/
var postEditURL = kcal_object.edit_url;

/**
*$ gets css property in RGB. this converts to hex for the edit calendar form
*@param string rgb
*/

var rgb2hex = function(rgb){
    if (!rgb.match(/^#[A-Fa-f0-9]{3,6}$/)){
        rgb = rgb.match(/^rgb\((\d+),\s*(\d+),\s*(\d+)\)$/);
        function hex(x) {
                return ("0" + parseInt(x).toString(16)).slice(-2);
        }
        return "#" + hex(rgb[1]) + hex(rgb[2]) + hex(rgb[3]);
    }
    else{
    	return rgb;
    }
}
/**
* hides the calendar name element and the highlight colour element, but displays save button (for delete)
*/
var show_hide_edit_calendar_elements = function(){
    jQuery('#calEditElements').hide();
    jQuery('#calEditElements :input[name=calendarName]').val('');
    jQuery('#calEditButtons').show();
}

/**
* displays the notice or message element based on id
* @param string message
* @param string id
*/
var display_complete_message = function(message, id){
    jQuery('p.message',id).html(message).show();
}
/**
* set events for calendar list on lhs menu
* one click function for the text
* one click function for the checkbox
* @param object calendarObj
*/
var set_calendar_list_events = function(calendarObj){
    if (jQuery('#leftColAdmin ul').length == 1){
        jQuery('#leftColAdmin ul li').each(function(){
            //should already have cursor pointer
            jQuery(':input',this).click(function(){
                    //filter calendar - start tuesday
                    (jQuery(this).is(':checked')) ?add_remove_event_sources('addEventSource',jQuery(this).val(), calendarObj):add_remove_event_sources('removeEventSource',jQuery(this).val(), calendarObj);
            });
        });
    }
}
/**
* Load css into body styles tag when user updates a main calendar
* Events are re-fetched when a calendar is updated to apply new css styles
*/
var update_cal_css = function(){
    jQuery.get(ajaxurl + '?action=adminCalendarStyle',({'request':'style'}),function(data){
        if (data != 'false'){
            jQuery('style:eq(0)').empty();
            jQuery('style:eq(0)').html(data);
        }
    });
}
/**
* Update the calendar list when a user adds or edits a new event when a calendar is added or updated
*/
var update_add_edit_select_cal = function(){
    if (jQuery('#leftColAdmin ul').length == 1){
        jQuery(':input[name=calendarID] option:gt(0)','#frmEditEvents').remove();
        jQuery('#calsList span').each(function(index){
            jQuery(':input[name=calendarID]','#frmEditEvents').append('<option value="'+jQuery(this).attr('id').replace("cal","")+'">'+jQuery(this).html()+'</option>');
            jQuery(':input[name=calendarID] option:eq('+(1+index)+')','#frmEditEvents').css('color',jQuery(this).parent().parent().css('color'));
        });
    }
}
/**
* Remove and replace calendars list on lhs menu with data returned from ajax request
* @param object calendarObj
* @see set_calendar_list_events()
* @see update_add_edit_select_cal
*/
var load_cals_list = function(calendarObj){
    jQuery.get(ajaxurl,({'action' : 'adminListCalendars', 'request':'list'}),function(data){
        jQuery('#calsList').remove();
        jQuery('#leftColAdmin p:eq(0)').after(data);
        //add events here for checkboxes and text-data (all should have cursor:pointer)
        set_calendar_list_events(calendarObj);
        update_add_edit_select_cal();
    });
}

/**
* Common method to update form date and time fields in the edit events form
* This is used for drag/drop, event resize (time) and for day click
* Drag/drop submits the edit form blindly
* start and end parameters are date objects set by fullCalendar
* @param object start
* @param object end
* @param object form
*/
var set_add_edit_date_vals = function(start, end, form){
    var startDay = (start.getDate() < 10) ? "0" + start.getDate() : start.getDate();
    var endDay = (end.getDate() < 10) ? "0" + end.getDate() : end.getDate();
    var startHours = parseInt(start.getHours(),10);
    var startMM = (parseInt(start.getMinutes(),10) < 10)? "0" + parseInt(start.getMinutes(),10) : parseInt(start.getMinutes(),10);
    var startTOD = (parseInt(start.getHours(),10) > 11) ? "PM" : "AM";
    startHours = (startHours < 10) ? "0" + startHours : startHours;
    if (startHours > 12){
        startHours -= 12;
    }
    if (startHours == 0){
        startHours = 12;
    }
    var endHours = parseInt(end.getHours(),10);
    endHours = (endHours < 10) ? "0" + endHours : endHours;
    if (endHours > 12) {
        endHours -= 12;
    }
    if (endHours == 0) {
        startHours = 12;
    }
    var endMM = (parseInt(end.getMinutes(),10) < 10) ? "0" + parseInt(end.getMinutes(),10) : parseInt(end.getMinutes(),10);
    var endTOD = (parseInt(end.getHours(),10) > 11) ? "PM" : "AM";
    var startMN = ((start.getMonth()+1) < 10) ? "0" + (start.getMonth()+1) : (start.getMonth()+1);
    var endMN = ((end.getMonth()+1) < 10) ? "0" + (end.getMonth()+1) : (end.getMonth()+1);
    var startDate = start.getFullYear() + '-' + startMN + '-' + startDay;
    var endDate = end.getFullYear() + '-' + endMN + '-' + endDay;
    jQuery(':input[name=_kcal_recur_eventStartDate]',form).val(startDate);
    jQuery(':input[name=_kcal_recur_eventEndDate]',form).val(endDate);
    jQuery(":input[name=_kcal_recurStartTime]", form).val(startHours + ":" + startMM + " " + startTOD);
    jQuery(":input[name=_kcal_recurEndTime]", form).val(endHours + ":" + endMM + " " + endTOD);
}
/**
* Show hide start and end date for editing recurring events.
* Dates can only be modified if single instance is selected, else only times can be modified
* @param true|false disable
*/
var toggle_edit_recur_options = function(disable){
    jQuery(':input[name=_kcal_recur_eventStartDate]','#frmEditRecurring').attr('disabled',disable);
    jQuery(':input[name=_kcal_recur_eventEndDate]','#frmEditRecurring').attr('disabled',disable);
}

/**
* Set the field values for edit a recurring event dialog before it is opened
* The form is reset prior to being re-opened
* eventDetails is the original object set and passed from fullCalendar
* @param object eventDetails
* @see toggle_edit_recur_options()
* @see set_add_edit_date_vals()
*/
var open_edit_recurring_dialog = function(eventDetails){
    jQuery('p.message','#dlgEditRecurring').empty();
    document.getElementById('frmEditRecurring').reset();
    toggle_edit_recur_options(false);

    var form = jQuery('#frmEditRecurring');
	var eventID = eventDetails.id.split('-');

    jQuery(':input[name=recurrenceID]',form).val(eventID[1]);
    jQuery(':input[name=eventID]',form).val(eventDetails.id);
    set_add_edit_date_vals(eventDetails.start, eventDetails.end, form);
    form.show();
    jQuery('#dlgEditRecurring h2').html('Edit Recurring Event: ' + eventDetails.title);
    jQuery('#dlgEditRecurring').fadeIn();

}
/**
 * This is from the WP Post type admin screen
 * @param object eventDetails
 */
var open_edit_recurring_dialog_single = function(eventDetails){
    jQuery('p.message','#dlgEditRecurring').empty();
    var form = jQuery('#frmEditRecurring');
    jQuery(':input[name=recurrenceID]',form).val(eventDetails.recurrenceID);
    jQuery(':input[name=eventID]',form).val(eventDetails.id);
    set_add_edit_date_vals(eventDetails.start,eventDetails.end,form);
    form.show();
    jQuery('#dlgEditRecurring h2').html('Edit Recurring Event: ' + eventDetails.title);
    jQuery('#dlgEditRecurring').fadeIn();

}
/**
 * This is from the WP Post type admin screen
 * @see open_edit_recurring_dialog_single()
 * @see open_delete_event_dlg_single()
 */
var set_single_edit_del_events = function(){
    jQuery("label.recur-edit").off("click");
    jQuery("label.del-recur").off("click");
    jQuery("label.recur-edit").on("click", function(){
        var postID = jQuery(this).data("post");
        var id = jQuery(this).attr("id").split("-");
        var recurID = (id.length == 3) ? id[2] : "";
        var start = new Date(jQuery(this).data("start"));
        var end = new Date(jQuery(this).data("end"));
        var eventDetails = {
            title: jQuery("input[name=post_title]").val(),
            id: postID + "-" + recurID,
            recurrenceID : recurID,
            start: start,
            end: end
         };
         open_edit_recurring_dialog_single(eventDetails);
     });
     jQuery("label.del-recur").on("click", function(){
        var postID = jQuery(this).data("post");
        var id = jQuery(this).attr("id").split("-");
        var recurID = (id.length == 3) ? id[2] : "";
        var eventDetails = {
            title: "Recurring Event " + jQuery("input[name=post_title]").val(),
            id: postID,
            recurrenceID : recurID
         };

         open_delete_event_dlg_single(eventDetails);
     });
}
/**
* Sets values in the edit event form. This is used for both drag/drop editing (blind) and event click editing
* eventDetails is the original object set and passed from fullCalendar
* @param object eventDetails
* @param object form
* @see set_add_edit_date_vals()
*/
var set_edit_form_element_vals = function(eventDetails, form) {
    var calendarInfo = eventDetails.className.toString().split("_");
    var calendarID = calendarInfo[calendarInfo.length-1];
    jQuery(':input[name=eventTitle]',form).val(eventDetails.title);
    jQuery(':input[name=calendarID]',form).val(calendarID);
    jQuery(':input[name=eventType]',form).val(eventDetails.eventType);
    jQuery(':input[name=description]',form).val(eventDetails.description);
    jQuery(':input[name=location]',form).val(eventDetails.location);
    set_add_edit_date_vals(eventDetails.start,eventDetails.end,form);
    if (eventDetails.allDay == true){
        jQuery(':input[name=allDayEvent]',form).attr('checked',true);
    }
    jQuery(':input[name=recurrence]',form).val(eventDetails.recurrence);
    if (eventDetails.recurrence !== 'None'){
        var recurrenceDetails = eventDetails.recurrenceDescription.toString().split(" ");
        var recurrenceInterval = (recurrenceDetails.length > 1)?recurrenceDetails:"1";
        jQuery(':input[name=recurrenceInterval]',form).val(recurrenceInterval);
        jQuery(':input[name=recurrenceEnd]',form).val(eventDetails.recurrenceEnd);
    }
    if (eventDetails.altUrl){
        jQuery(':input[name=detailPage]',form).attr('checked',true);
        jQuery(':input[name=detailsAlternateURL]',form).val(eventDetails.altUrl);
    }
    jQuery(':input[name=eventSaveType]',form).val('u');
    jQuery(':input[name=eventID]',form).val(eventDetails.id);

    if (eventDetails.imagePath){
        jQuery(":input[name=imagePath]", form).val(eventDetails.imagePath);
        jQuery("#eventImage_src").attr("src", eventDetails.imagePath);
    }
}
/**
* Reset delete event form based on event clicked, and open dialog
* eventDetails is the original object set and passed from fullCalendar
* @param object eventDetails
*/
var open_delete_event_dlg = function(eventDetails){
    jQuery('p.message','#dlgDeleteEvent').empty();
    var form = jQuery('form#frm_delete_event');
    if (jQuery('form#frm_delete_event').length == 1){
        document.getElementById('frm_delete_event').reset();
    }
    jQuery(':input[name=recurrenceID]',form).val('');
    jQuery('#tbRecurring','#dlgDeleteEvent').hide();
    if (eventDetails.recurrence != 'None'){
        if (eventDetails.recurrenceID){
            jQuery(':input[name=recurrenceID]',form).val(eventDetails.recurrenceID);
            jQuery('#tbRecurring','#dlgDeleteEvent').show();
        }
    }
    jQuery(':input[name=eventID]',form).val(eventDetails.id);
    form.show();
    jQuery('#dlgDeleteEvent h2').html('Delete: '+eventDetails.title);
    jQuery('#dlgDeleteEvent').fadeIn();
}
/**
* Reset delete event form based on event clicked, and open dialog
* eventDetails is the original object set and passed from fullCalendar
* @param object eventDetails
*/
var open_delete_event_dlg_single = function(eventDetails){
    jQuery("#frm_delete_event").show();
    jQuery('p.message','#dlgDeleteEvent').empty();
    jQuery(':input[name=recurrenceID]',"#frm_delete_event").val('');
    if (eventDetails.recurrence != 'None'){
        if (eventDetails.recurrenceID){
            jQuery(':input[name=recurrenceID]',"#frm_delete_event").val(eventDetails.recurrenceID);
        }
    }
    jQuery(':input[name=eventID]',"#frm_delete_event").val(eventDetails.id);
    jQuery('#dlgDeleteEvent h2').html('Delete: '+eventDetails.title);
    jQuery('#dlgDeleteEvent').fadeIn();
}

/**
 * Called when deletion of a current event is confirmed from fullCalendar view
 * @param object form
 * @see set_single_edit_del_events()
 */
var complete_delete_recur_event = function(form){
    var recurID = jQuery(':input[name=recurrenceID]',form).val();
    jQuery("#del-recur-" + recurID).parents("li").remove();
    jQuery(':input[name=recurrenceID]',form).val("");
    jQuery(':input[name=eventID]',form).val("");
    set_single_edit_del_events();
}
/**
 * Called from fullcalendar when edit for a recurring event is confirmed
 * @param object form
 * @see set_single_edit_del_events
 */
var complete_edit_recur_event = function(form){
	var days = ["Sun","Mon","Tue","Wed","Thu","Fri","Sat"];
	var months = ["Jan","Feb","Mar","Apr","May","Jun","Jul","Aug","Sep","Oct","Nov","Dec"];
	var recurID = jQuery(':input[name=recurrenceID]',form).val();
	var eventID = jQuery(":input[name=eventID]", form).val();
	var oldTime = jQuery("#edit-recur-" + recurID).parents("li").html().split("<span");
	var startDate = new Date(jQuery(':input[name=_kcal_recur_eventStartDate]',form).val() + " " + jQuery(":input[name=_kcal_recurStartTime]", form).val());
	var endDate = new Date(jQuery(':input[name=_kcal_recur_eventEndDate]',form).val() + " " + jQuery(":input[name=_kcal_recurEndTime]", form).val());
	var textDate = days[startDate.getDay()] + ", " + months[startDate.getMonth()]+ " " + startDate.getDate() + ", " + startDate.getFullYear();
	var textEndDate = days[endDate.getDay()] + ", " + months[endDate.getMonth()]+ " " + endDate.getDate() + ", " + endDate.getFullYear();
	var textTime;
	var textEndTime;
	if (startDate.getMinutes() < 10) {
		textTime = "0" + startDate.getMinutes();
	} else {
		textTime = startDate.getMinutes();
	}
	if (startDate.getHours() > 12) {
		textTime = startDate.getHours() - 12 + ':' + textTime + ' pm';
	} else {
		textTime = startDate.getHours() + ':' + textTime + ' am';
	}

	if (endDate.getMinutes() < 10) {
		textEndTime = "0" + endDate.getMinutes();
	} else {
		textEndTime = endDate.getMinutes();
	}
	if (endDate.getHours() > 12) {
		textEndTime = endDate.getHours() - 12 + ':' + textEndTime + ' pm';
	} else {
		textEndTime = endDate.getHours() + ':' + textEndTime + ' am';
	}

	if (textDate != textEndDate){
		textDate += " " + textTime + "-" + textEndDate + " " + textEndTime;
	}
	else{
		textDate += " " + textTime + "-" + textEndTime;
	}
	jQuery("#edit-recur-" + recurID).parents("li").html(textDate + "<span" + oldTime[1]);
	var dataPostStart = startDate.getFullYear()+ "-" + (startDate.getMonth() < 9 ? "0" + (1+startDate.getMonth()) : 1+startDate.getMonth()) + "-" + startDate.getDate() + " " + textTime;
	var dataPostEnd = endDate.getFullYear()+ "-" + (endDate.getMonth() < 9 ? "0" + (1+endDate.getMonth()) : 1+endDate.getMonth()) + "-" + endDate.getDate() + " " + textEndTime;
	jQuery("#edit-recur-" + recurID).attr("data-start", dataPostStart);
	jQuery("#edit-recur-" + recurID).attr("data-end", dataPostEnd);
	set_single_edit_del_events();
}

/**
* Submits an edit request when a user drags an event time, or drops an event to a new day
* This is a blind request so the user doesn't see it
* eventDetails fullCalendar objects, revertFunc is a callback that can be used if ajax fails, however, all events are refetched, so not used currently
* @param object eventDrop
* @param object calendarObj
*/
var send_drop_event = function(eventDrop, calendarObj) {

	var calEvent = eventDrop.event;

    var event = calEvent.id.toString();
    var eventID = event.split("-");
    var isRecurring = (eventID.length == 2) ? true : false;
    var recurrenceID = (eventID.length == 2) ? eventID[1] : "";
	var startStr = '';
	var endStr = '';

	if (calEvent.allDay === true) {
		var displayStart =  calEvent.extendedProps.displayStart.toString().replace(/\s[ap]m/, ':00');
		var displayEnd = calEvent.extendedProps.displayEnd.toString().replace(/\s[ap]m/, ':00');
		var startTOD = displayStart.toString().split(':');
		var endTOD = displayEnd.toString().split(':');

		if (calEvent.extendedProps.displayStart.toString().match(/pm/) == 'pm') {
			displayStart = displayStart.replace(startTOD[0], (parseInt(startTOD[0], 10) +  12) .toString() );
		} else {
			if (parseInt(startTOD[0], 10) < 10) {
				displayStart = displayStart.replace(displayStart.toString().substring(0,1), '0' + startTOD[0]);
			}
		}

		if (calEvent.extendedProps.displayEnd.toString().match(/pm/) == 'pm') {
			displayEnd = displayEnd.replace(endTOD[0], (parseInt(endTOD[0], 10) +  12) .toString() );
		} else {
			if (parseInt(endTOD[0], 10) < 10) {
				displayEnd = displayEnd.replace(displayEnd.toString().substring(0,1), '0' + endTOD[0]);
			}
		}

		startStr = calEvent.startStr.toString() + ' ' + displayStart;
		endStr = calEvent.startStr.toString() + ' ' + displayEnd;
	} else {
		startStr = calEvent.startStr.toString().replace('T', ' ');
		startStr = startStr.substring(0, 19);
		endStr = calEvent.endStr.toString().replace('T', ' ');
		endStr = endStr.substring(0, 19);
	}

    var params = {
        "_kcal_dropStartDate" : startStr,
        "_kcal_dropEndDate"   : endStr,
        "eventID" : eventID[0],
        "isRecurring" : isRecurring,
        "recurrenceID" : recurrenceID
    };

    jQuery.get(ajaxurl + '?action=adminEditEvents&request=event&input=u',(params),function(data){
        calendarObj.refetchEvents();
    });
}
/**
* this global function allows the fullcalendar defaults to be overridden by the admin functions so the same calendar code
* can be used on the public page
* @see open_delete_event_dlg()
* @see open_edit_recurring_dialog()
* @see send_drop_event()
* @return object
*/
var adminDefaults = function(){
	var today = new Date();
	var defaults = {
		initialDate: today,
		initialView: 'dayGridMonth',
		editable: true,
		businessHours: true,
		dayMaxEvents: true, // allow "more" link when too many events
		events: [],
		headerToolbar: {
			start: 'title',
			center: 'dayGridMonth,timeGridWeek,listMonth',
			end: 'today prev,next'
		},
		views: {
			listMonth: {
				buttonText: 'List'
			},
			timeGridWeek: {
				buttonText: 'Week'
			},
			dayGridMonth: {
				buttonText: 'Month'
			},
		},
		selectable: true,
		eventClick: function(eventObj){
			var calEvent = eventObj.event;
			show_event_details(eventObj);
			var dlg = '#dlgEventDetails';
			jQuery('#editEvent',dlg).unbind('click');
			jQuery('#deleteEvent',dlg).unbind('click');
			jQuery('#deleteEvent',dlg).click(function(){
				jQuery(dlg).fadeOut();
				open_delete_event_dlg(calEvent);
			});
			if (calEvent.extendedProps.recurrenceID && String(calEvent.extendedProps.recurrenceID).match(/^\d{1,6}$/)){
				jQuery('#editEvent',dlg).click(function(){
						jQuery(dlg).fadeOut();
						open_edit_recurring_dialog(calEvent);
				});
			}
			else{
				jQuery('#editEvent',dlg).click(function(){
					window.location.href = postEditURL + "&post=" + calEvent.id;
				});
			}
		},
		eventDrop: function(eventDropInfo){
			send_drop_event(eventDropInfo, calendarObj);
		},
		eventResize: function(eventDropInfo, calendarObj){
			send_drop_event(eventDropInfo, calendarObj);
		}
	}
	return defaults;
}
/**
* Submit ajax request to add a new event or edit a current one
* @param object form
* @param string dialog
* @see display_complete_message()
*/
var save_add_edit_events = function(form, dialog, calendarObj) {
	jQuery(form).hide();
	jQuery('#imgEditImgLoad').show();
	var params = jQuery(":input", form).serialize();
	var message = (jQuery(':input[name=eventSaveType]',form).val() == 'd') ? "Your event was deleted successfully. If you did not mean to do this, you can recover your main event from the trash, or add back recurring instances from the post edit screen " : "Your events were saved successfully";
	jQuery.get(ajaxurl + '?action=adminEditEvents&request=event&input='+jQuery(':input[name=eventSaveType]',form).val(),(params),function(data){
            if (!data.match(/true/)) {
				message = "There was an error saving your events:<br />" + data;
			}
            jQuery('#imgEditImgLoad').hide();
            display_complete_message(message,dialog);
            if (jQuery('#calendar').length == 1) {
                if (data.match(/true/)) {
					calendarObj.refetchEvents();
				}
            }
            else{
                if (jQuery(':input[name=eventSaveType]',form).val() == "d") {
                    complete_delete_recur_event(form);
                }
                else{
                    complete_edit_recur_event(form);
                }
            }
	});
}
jQuery(document).ready(function($){
	//admin methods start here
	//js events: new Calendar
    $(".datepicker").datepicker({
        dateFormat: "yy-mm-dd"
    });

    $('.timepicker').timepicker({
        timeFormat: 'hh:mm TT',
        ampm: true
    });

    if ($('#calendar').length == 1){

        var set_form_button_events = function(){
            $(':input[name=cancelEditEvent]','#frmEditEvents').click(function(){
                $('#dlgAddEditEvent').fadeOut();
                $('p.message','#dlgAddEditEvent').empty();
                document.getElementById('frmEditEvents').reset();
            });
            $('#btnNewCalendar').click(function(){
                if ($('.permissions','#frmNewCalendar').length == 1){
                    $('.permissions :input[type=checkbox]','#frmNewCalendar').attr('checked', false);
                }
                $('#dlgNewCalendar p.message').empty();
                $('#dlgNewCalendar :input[name=calendarName]').val('');
                $('#dlgNewCalendar :input[name=backgroundColorNew]').val('#cccccc').css({'backgroundColor':'#ccc'});
                $('#dlgNewCalendar form:eq(0)').show();
                $('#dlgNewCalendar').fadeIn();
            });
            $('#btnCancelNewCalendar').click(function(){
                $(this).parents('.quickview-popup').fadeOut();
            });
            $('#btnEditCalendar').click(function(){
                $('#dlgEditCalendar p.message').empty();
                show_hide_edit_calendar_elements();
                $('#calEditElements').show();
                $('#calEditAction').val('edit');
                $(this).hide();
                $('#btnDeleteCalendar').hide();
            });
            $('#btnDeleteCalendar').click(function(){
                show_hide_edit_calendar_elements();
                $('#calEditAction').val('delete');
                $(this).hide();
                $('#btnEditCalendar').hide();
                display_complete_message('Are you sure you want to delete the following calendar?<hr />','#dlgEditCalendar');
            });
            $('#btnCancelEditCalendar').click(function(){
                show_hide_edit_calendar_elements();
                $('#calEditButtons').hide();
                $(this).parents('.quickview-popup').fadeOut();
                $('#calEditAction').val('');
                $('#calEditID').val('');
            });
            $('#btnCancelDeleteEvent').click(function(){
                $('#tbRecurring','#dlgDeleteEvent').hide();
                $(this).parents('.quickview-popup').fadeOut();
            });
            $('#cancelEditRecurEvent').click(function(){
                $(this).parents('.quickview-popup').fadeOut();
            });
            $('#recurEdit_all','#frmEditRecurring').click(function(){
                toggle_edit_recur_options(true);
            });
            $('#recurEdit_this','#frmEditRecurring').click(function(){
                toggle_edit_recur_options(false);
            });
        };
        set_calendar_list_events(calendarObj);
        set_form_button_events();

        /**
        * submit edit recurring events form
        */
        $('#frmEditRecurring').submit(function(e){
            e.preventDefault();
            $('p.message','#dlgEditRecurring').empty();
            var message = "";
            if ($(':input[name=eventTitle]',this).val() == "") {
                message += "Provide a title for your event<br />";
            }
            if (!$(':input[name=_kcal_recur_eventStartDate]',this).val().match(/^20[0-9]{2}([-])(0[1-9]|1[012])([-])([012][0-9]|3[01])$/)) {
                message += "Provide a valid starting date (YYYY-MM-DD)<br />";
            }
            if (!$(':input[name=_kcal_recur_eventEndDate]',this).val().match(/^20[0-9]{2}([-])(0[1-9]|1[012])([-])([012][0-9]|3[01])$/)) {
                message += "Provide a valid ending date (YYYY-MM-DD)<br />";
            }
            if(message == "") {
                toggle_edit_recur_options(false);
                save_add_edit_events(this,'#dlgEditRecurring', calendarObj);
            }
            else{
                display_complete_message(message+'<hr />','#dlgEditRecurring');
            }
            return false;
        });
        /**
        * delete events functions
        */
        $('#frm_delete_event').submit(function(e){
            e.preventDefault();
            $('p.message','#dlgDeleteEvent').empty();
            if ($(':input[name=eventID]',this).val().match(/^\d+/) == false){
                $(this).hide();
                $('p.message','#dlgDeleteEvent').html('An event was not selected. Please retry');
            }
            else if (isNaN($(':input[name=recurrenceID]',this).val()) == false && $(':input[name=recurDelete]',this).val() == ""){
                $('p.message','#dlgDeleteEvent').html('Select which instance you want to delete.');
            }
            else{
                save_add_edit_events(this,'#dlgDeleteEvent', calendarObj);
            }
            return false;
        });
    }
    /**
     * From the main edit screen
     */
    else {
		if ($("#kcal_eventRepeat").length == 1) {
			set_single_edit_del_events();

				$('#btnCancelDeleteEvent').click(function() {
				$('#tbRecurring','#dlgDeleteEvent').hide();
				$(this).parents('.quickview-popup').fadeOut();
			});
			$("#btnDeleteEvent").on("click", function(e) {
				e.preventDefault();
				$('p.message','#dlgDeleteEvent').empty();
				if ($(':input[name=eventID]',"#dlgDeleteEvent").val().match(/^\d+/) == false){
					$("#frm_delete_event").hide();
					$('p.message','#dlgDeleteEvent').html('An event was not selected. Please retry');
				}
				else if (isNaN($(":input[name=recurrenceID]","#dlgDeleteEvent").val()) == false && $(":input[name=recurDelete]","#dlgDeleteEvent").val() == ""){
					$("p.message","#dlgDeleteEvent").html("Select which instance you want to delete.");
				}
				else{
					save_add_edit_events($("#frm_delete_event"),'#dlgDeleteEvent', calendarObj);
				}
				return false;
			});

			$('#saveRecurEvent').on("click",function(e) {
					e.preventDefault();
					$('p.message','#dlgEditRecurring').empty();
					var message = "";
					if (!$(':input[name=_kcal_recur_eventStartDate]',"#frmEditRecurring").val().match(/^20[0-9]{2}([-])(0[1-9]|1[012])([-])([012][0-9]|3[01])$/)){
						message += "Provide a valid starting date (YYYY-MM-DD)<br />";
					}
					if (!$(':input[name=_kcal_recur_eventEndDate]',"#frmEditRecurring").val().match(/^20[0-9]{2}([-])(0[1-9]|1[012])([-])([012][0-9]|3[01])$/)){
						message += "Provide a valid ending date (YYYY-MM-DD)<br />";
					}
					if(message == ""){
						save_add_edit_events("#frmEditRecurring",'#dlgEditRecurring', calendarObj);
					}
					else{
						display_complete_message(message+'<hr />','#dlgEditRecurring');
					}
					return false;
			});
			$('#cancelEditRecurEvent').click(function() {
				$(this).parents('.quickview-popup').fadeOut();
			});
		}
	}
	//help
	$('#aCalInfo').on("click", function(e) {
		e.preventDefault();
		$("#calInfo").show();
	});

	/**
	* Dialog pop-up close
	*/
	$('.close-btn').click(function(e) {
		e.preventDefault();
		$(this).parent().parent().fadeOut();
	});

	$(document).bind('keydown', function(e) {
		if(e.which == 27) {
			$('.quickview-popup').fadeOut();
		}
	});


    //change the active, hover and initial state icon for side navigation menu
    if ($('.menu-icon-event img').length == 1){
        var staffNavItem = $('.menu-icon-event img');
        var staffNavParent = $('.menu-icon-event');
        var imgSrc = $(staffNavItem).attr("src");
        var imgPath = imgSrc.split("img/");
        var imgBase = imgPath[1];


        var set_active_img = function(imgPath){
            var newImgBase = "calendar_white.png";
            $(staffNavItem).attr("src", imgPath + "img/" + newImgBase);
        };

        $('.menu-icon-event').on("mouseover", function(){
            if ($(staffNavParent).hasClass("wp-has-current-submenu") === false) {
                var newImgBase = "calendar_hover.png";
                $(staffNavItem).attr("src", imgPath[0] + "img/" + newImgBase);
            }
        });
        $('.menu-icon-event').on("mouseout", function(){
            if ($(staffNavParent).hasClass("wp-has-current-submenu") === false) {
                $(staffNavItem).attr("src", imgPath[0] + "img/" + imgBase);
            }
        });

        $('.menu-icon-event').on("click", function(){
           if ($(this).hasClass("wp-has-current-submenu")){
               set_active_img(imgPath[0]);
           }
        });

        if ($('.menu-icon-event').hasClass("wp-has-current-submenu")) {
            set_active_img(imgPath[0]);
        }
    }
});
var formField;
var imgField;
var restoreEditor = window.send_to_editor;
if (jQuery("input[name='kCal_mb_nonce']").length > 0) {

    jQuery("[id^=\"uploadimage_kcal\"]").on("click", function(e) {
        var id = jQuery(this).attr("id");
        formField = id.replace("uploadimage","");
        imgField = "img" + formField;
        tb_show('', 'media-upload.php?type=image&amp;TB_iframe=true');
        window.send_to_editor = function(html) {
            var fileURL = html.match(/http\:(s)?[^\s]*\.(png|jpg|jpeg|gif|bmp)/);

            if (fileURL != 'undefined' && fileURL != null) {
                jQuery('#' + formField).val(fileURL[0]);
                jQuery('#' + imgField).attr('src', fileURL[0]);
            }

            tb_remove();
            window.send_to_editor = restoreEditor;

          };
        return false;
    });

}
