$(document).ready(function() {
	var fullcalendarscheduler_mouseDown = false;
	document.body.onmousedown = function() { 
		fullcalendarscheduler_mouseDown = true;
	};
	document.body.onmouseup = function() {
		fullcalendarscheduler_mouseDown = false;
	};
	
	var fullcalendarscheduler_now = new Date();
	var fullcalendarscheduler_date = ('0' + fullcalendarscheduler_now.getDate()).slice(-2);
	var fullcalendarscheduler_month = ('0' + (fullcalendarscheduler_now.getMonth() + 1)).slice(-2);
	var fullcalendarscheduler_today = fullcalendarscheduler_now.getFullYear()+'-'+fullcalendarscheduler_month+'-'+fullcalendarscheduler_date;
	
	var isLocaleFr = fullcalendarscheduler_initialLangCode == 'fr'; 
	
	
	/* Debut Calendar du menu de Gauche */
	$('#agf_session_scheduler_mini').fullCalendar({
		monthNames: (isLocaleFr ? ["Janvier", "Février", "Mars", "Avril", "Mai", "Juin", "Juillet", "Août", "Septembre", "Octobre", "Novembre", "Décembre"] : ''),
	    dayNamesShort: (isLocaleFr ? ["Dim", "Lun", "Mar", "Mer", "Jeu", "Ven", "Sam"] : ''),
		header: {
			schedulerLicenseKey: 'GPL-My-Project-Is-Open-Source',
			left: 'title',
			center: 'prev,today,next',
			right: ''
		},
		locale: fullcalendarscheduler_initialLangCode,
		defaultDate: fullcalendarscheduler_today, // Must be yyyy-mm-dd
		defaultView: 'month',
		editable: false,
		aspectRatio: 0.5,
		dayClick: function(date, jsEvent, view, resourceObj) {
			console.log('dayClick event called and gotoDate is triggered to', date.format());
			$('#agf_session_scheduler').fullCalendar('gotoDate', date);
		},
		eventAfterAllRender: function( view ) {
			// Force enable "today" button
			$('#agf_session_scheduler_mini .fc-today-button').removeClass('fc-state-disabled');
			$('#agf_session_scheduler_mini .fc-today-button').prop('disabled', false);
		}
		
	});
	
	$('#agf_session_scheduler_mini .fc-today-button').click(function() {
		console.log('today button is triggered on mini calendar');
		$('#agf_session_scheduler').fullCalendar('today');
	});
	/* Fin Calendar du menu de Gauche */
	
	
	// TODO voir https://fullcalendar.io/docs/event_ui/ pour plus de détail sur les events
	
	/* Début Calendar centrale */
	$('#agf_session_scheduler').fullCalendar({
		monthNames: (isLocaleFr ? ["Janvier", "Février", "Mars", "Avril", "Mai", "Juin", "Juillet", "Août", "Septembre", "Octobre", "Novembre", "Décembre"] : ''),
		slotLabelFormat: (isLocaleFr ? "HH:mm" : ''),
		header: {
			schedulerLicenseKey: 'GPL-My-Project-Is-Open-Source',
			left: 'title',
			center: '',
			right: 'prev,today,next'
		},
		locale: fullcalendarscheduler_initialLangCode,
		defaultDate: fullcalendarscheduler_today, // Must be yyyy-mm-dd
		defaultView: 'agendaWeek',
		editable: true,
		selectable: true,
		selectHelper: true,
		aspectRatio: fullcalendarscheduler_aspectRatio,
		minTime: fullcalendarscheduler_minTime, // default 00:00
		maxTime: fullcalendarscheduler_maxTime, // default 23:00
		eventOverlap: true,
		slotDuration:'01:00:00',
		snapDuration: fullcalendarscheduler_snapDuration,
		businessHours: [
			{ // Jours de semaines
				dow: [ 1, 2, 3, 4, 5 ], // Lundi, Mardi, Mercredi, Jeudi, Vendredi
				start: fullcalendar_scheduler_businessHours_week_start, // 8am
				end: fullcalendar_scheduler_businessHours_week_end // 6pm
				//,constraint: 'available_hours' // TODO à voir comment marche la contrainte, actuellement tous les events peuvent être déplacés dans toutes les plages horaires => à voir si je restreint 
			},
//			{ // Weekend
//				dow: [ 0, 6 ], // Dimanche, Samedi
//				start: fullcalendar_scheduler_businessHours_weekend_start, // 10am
//				end: fullcalendar_scheduler_businessHours_weekend_end // 4pm
//			}
		],

		//// uncomment this line to hide the all-day slot
		allDaySlot: false,

		resources: fullcalendar_scheduler_resources_allowed, // Tableau d'objet
		//events: fullcalendar_scheduler_events_by_resource, // Tableau d'objet
		eventClick: function(event, jsEvent, view) {
			console.log('eventClick called: ', event, jsEvent);
			
			if (!$(jsEvent.target.parentElement).hasClass('ajaxtool_link') && !$(jsEvent.target.parentElement).hasClass('ajaxtool') && !$(jsEvent.target).hasClass('classfortooltip'))
			{
				// show form, seulement si le clic ne provient pas d'un lien "action rapide"
				showEventDialog(view, event.start, event.end, event);	
			}
		},
		select: function(start, end, jsEvent, view) {
			console.log('select called: ', start.format());
			
			// show form
			showEventDialog(view, start, end);
		},
		dayClick: function(date, jsEvent, view) {
			console.log('dayClick called: ', date.format());
		},
		/*eventDragStart: function(event, jsEvent, ui, view) {
			console.log('eventDragStart : ', event, jsEvent, ui, view);
		},*/
		/*eventDragStop: function(event, jsEvent, ui, view) {
			console.log('eventDragStop : ', event, jsEvent, ui, view);
		},*/
		eventDrop: function(event, delta, revertFunc, jsEvent, ui, view) {
			console.log('eventDrop called and delta is: '+delta.toString(), event);
			
			// Gestion du cas d'erreur si on passe un event du bandeau "allday" sur une plage horaire, l'objet perd son attribut "end"
			if (event.end == null)
			{
				event.end = event.start.clone();
				event.end.add('hour', 1); // @see "slotDuration" (init à +1 heure)
			}

			$.ajax({
				url: fullcalendarscheduler_interface
				,dataType: 'json'
				,data: {
					json: 1
					,put: 'updateTimeSlotCalendrier' // update crénau horaire et ressource associée
					,fk_agefodd_session_calendrier: event.id
					,start: event.start.format('YYYY-MM-DD HH:mm')
					,end: event.end !== null && event.end !== "undefined" ? event.end.format('YYYY-MM-DD HH:mm') : ''
					,deltaInSecond: delta.asSeconds()
					,dateFrom: event.start.format('YYYY-MM-DD')
				}
			}).fail(function(jqXHR, textStatus, errorThrown) {
				console.log('Error: jqXHR, textStatus, errorThrown => ', jqXHR, textStatus, errorThrown);
				revertFunc();
			}).done(function(response, textStatus, jqXHR) {
				if (typeof response.data !== 'undefined' && typeof response.data.event !== 'undefined')
				{
					event = _updateEvent(event, response.data);
				}
				console.log('Done: ', response);
			});
		},
		/*eventResizeStart: function(event, jsEvent, ui, view) {
			console.log('eventResizeStart : ', event, jsEvent, ui, view);
		},
		eventResizeStop: function(event, jsEvent, ui, view) {
			console.log('eventResizeStop : ', event, jsEvent, ui, view);
		},*/
		eventResize: function(event, delta, revertFunc, jsEvent, ui, view) {
			console.log('eventResize called and delta is: '+delta.toString(), event);
			//console.log(delta.asSeconds());
			
			$.ajax({
				url: fullcalendarscheduler_interface
				,dataType: 'json'
				,data: {
					json: 1
					,put: 'updateTimeSlotCalendrier' // update crénau horaire
					,fk_agefodd_session_calendrier: event.id
					,start: event.start.format('YYYY-MM-DD HH:mm')
					,end: event.end.format('YYYY-MM-DD HH:mm')
					,deltaInSecond: delta.asSeconds()
					,dateFrom: event.start.format('YYYY-MM-DD')
				}
			}).fail(function(jqXHR, textStatus, errorThrown) {
				console.log('Error: jqXHR, textStatus, errorThrown => ', jqXHR, textStatus, errorThrown);
				revertFunc();
			}).done(function(response, textStatus, jqXHR) {
				if (typeof response.data !== 'undefined' && typeof response.data.event !== 'undefined')
				{
					event = _updateEvent(event, response.data);
				}
				console.log('Done: ', response);
			});
			
		},
		
		viewRender: function( view, element ) {
			console.log('viewRender called: ', view, element);
//console.log(111111, view.start.format('YYYY-MM-DD 00:00:00'), view.end.add(-1, 'days').format('YYYY-MM-DD 23:59:59'));
			$.ajax({
				url: fullcalendarscheduler_interface
				,dataType: 'json'
				,data: {
					json: 1
					,get: 'getAgefoddSessionCalendrier' // get all events from dates and code
					,fk_agefodd_session: fk_agefodd_session
					,dateStart: view.start.format('YYYY-MM-DD 00:00:00')
					,dateEnd: view.end.add(-1, 'days').format('YYYY-MM-DD 23:59:59')
				}
			}).fail(function(jqXHR, textStatus, errorThrown) {
				console.log('Error: jqXHR, textStatus, errorThrown => ', jqXHR, textStatus, errorThrown);
			}).done(function(response, textStatus, jqXHR) {
				console.log('viewRender Done: ', response);
				
				view.calendar.removeEvents();
				view.calendar.addEventSource(response.data.TEvent);
			});
		},
		eventRender: function(event, element, view) {
			if (event.id != null && event.id != 'undefined')
			{
				//console.log(event);
				var action_delete = '<a class="ajaxtool_link" href="javascript:delete_event('+event.id+');">'+fullcalendarscheduler_picto_delete+'</a>';
				
				if (event.startEditable) element.find('.fc-content').append('<div class="ajaxtool">'+action_delete+'</div>');
				
				if (event.calendrier_type_label.length > 0) element.find('.fc-content').append('<div class="calendrier_type">('+event.calendrier_type_label+')</div>');
				
				var liste_participant = $('<div class="liste_participant"></div>');
				var liste_formateur = $('<div class="liste_formateur"></div>');
				
				liste_formateur.append(event.TNomUrlFormateur.join('<br />'));
				
				element.find('.fc-content').append(liste_participant); // TODO à compléter
				element.find('.fc-content').append(liste_formateur); // TODO à compléter
				
				element.find('.fc-content a').css('color', element.css('color'));
				
				if (!event.startEditable)
				{
					element.find('.fc-bg').css({
						background: '#ccc'
						,opacity:0.95
					});
				}
			}
		},
		eventAfterAllRender: function (view) {
			// Pour un peu plus de confort pour éviter de bataillé avec l'adaptation de la hauteur du bloc sur le hover qui suit
			$('.fc-resizer').css('height', '12px');
			
			$('.fc-content').hover(function(jsEvent) {
				if (!fullcalendarscheduler_mouseDown)
				{
					var target = $(this).parent();
					var origin_height = parseInt(target.css('height'));
					target.data('originHeight', origin_height);
					
					if (origin_height < parseInt($(this).css('height'))) target.css('height', parseInt($(this).css('height'))+10);
				}
			}, function(jsEvent) {
				if (!fullcalendarscheduler_mouseDown)
				{
					var target = $(this).parent();
					target.css('height', target.data('originHeight'));
				}
			});
		}
	});
	/* Fin Calendar centrale */
	
	_updateEvent = function(event, data)
	{
		event = data.event;
		$('#agf_session_scheduler').fullCalendar('removeEvents', event.id);
		$('#agf_session_scheduler').fullCalendar('renderEvent', event);
	};
	
	delete_event = function(id)
	{	
		var div = $('<div>').html(fullcalendarscheduler_content_dialog_delete);
		div.dialog({
			modal: true
			,width: 'auto'
			,title: fullcalendarscheduler_title_dialog_delete_event
			,buttons: [
				{
					text: fullcalendarscheduler_button_dialog_confirm
					,icons: { primary: "ui-icon-check" }
					,click: function() {
						
						self = this;
						
						$.ajax({
							url: fullcalendarscheduler_interface
							,dataType: 'json'
							,data: {
								json: 1
								,put: 'deleteCalendrier'
								,fk_agefodd_session_calendrier: id
								,delete_cal_formateur: div.find('[name=delete_cal_formateur]').prop('checked') ? 1 : 0
							}
						}).fail(function(jqXHR, textStatus, errorThrown) {
							console.log('Error: jqXHR, textStatus, errorThrown => ', jqXHR, textStatus, errorThrown);
							$( self ).dialog( "close" );
						}).done(function(response, textStatus, jqXHR) {
							console.log('Done: ', response);

							if (response.TError.length > 0)
							{
								for (var x in response.TError)
								{
									$.jnotify(response.TError[x], 'error');
								}
							}
							else
							{
								var view = $('#agf_session_scheduler').fullCalendar('getView');
								view.calendar.removeEvents(id);
								
								$( self ).dialog( 'close' );
							}
						});
					}
				},
				{
					text: fullcalendarscheduler_button_dialog_cancel
					,icons: { primary: 'ui-icon-close' }
					,click: function() {
						$( this ).dialog( 'close' );
					}
				}
			]
		});
	};	
	
	
	showEventDialog = function(view, start, end, event)
	{
		if (typeof event != 'undefined') fullcalendarscheduler_div.data('fk-actioncomm', event.id);
		else fullcalendarscheduler_div.data('fk-actioncomm', 0);

        fullcalendarscheduler_div.dialog({
			modal: true
			,width: 'auto'
			,title: (typeof event !== 'undefined') ? fullcalendarscheduler_title_dialog_update_event : fullcalendarscheduler_title_dialog_create_event
			,buttons: [
				{
					text: (typeof event !== 'undefined') ? fullcalendarscheduler_button_dialog_update : fullcalendarscheduler_button_dialog_add
					,icons: { primary: "ui-icon-check" }
					,click: function() {
						
						self = this;
						
						$( self ).dialog( 'close' ); // à voir si le fait de close maintenant ne pose pas problème plus tard
						
						fullcalendarscheduler_div.find(':disabled').removeAttr('disabled'); // Pour pouvoir récupérer les valeurs avec un serialize
						var dataObject = fullcalendarscheduler_div.serializeObject();
						
						dataObject.json = 1;
						dataObject.put = 'createOrUpdateCalendrier';
						dataObject.fk_agefodd_session_calendrier = (typeof event !== 'undefined') ? event.id : 0;
						dataObject.dateFrom = view.start.format('YYYY-MM-DD');
						dataObject.dateStart = view.start.format('YYYY-MM-DD 00:00:00');
						var tmp_dateEnd = view.end.clone(); // need to clone to use subtract without affecting the original date
						dataObject.dateEnd = tmp_dateEnd.subtract(1, 'days').format('YYYY-MM-DD 23:59:59');
						
						$.ajax({
							url: fullcalendarscheduler_interface
							,type: 'POST' 
							,dataType: 'json'
							,data: dataObject
						}).fail(function(jqXHR, textStatus, errorThrown) {
							console.log('Error: jqXHR, textStatus, errorThrown => ', jqXHR, textStatus, errorThrown);
							$( self ).dialog( "close" );
						}).done(function(response, textStatus, jqXHR) {
							console.log('Done: ', response);

							if (response.TError.length > 0)
							{
								for (var x in response.TError)
								{
									$.jnotify(response.TError[x], 'error');
								}
							}
							else
							{
								view.calendar.removeEvents();
								view.calendar.addEventSource(response.data.TEvent);
							}
							
						});
						
					}
				},
				{
					text: fullcalendarscheduler_button_dialog_cancel
					,icons: { primary: 'ui-icon-close' }
					,click: function() {
						$( this ).dialog( 'close' );
					}
				}
			]
			,open: function( jsEvent, ui ) {
				// Init fields
				
				initEventFormFields(start, end, event);
			}
		}).trigger('fullcalendarscheduler_trigger_show_event_dialog');
		
		
	};
	
	/* Permet de changer correctement la selection du contact après un chargement ajax sur un .change() du fk_soc */
	fullcalendarscheduler_div.find('#contactid').bind("DOMNodeInserted",function(e){
		// Déclanchement de l'action uniquement sur la dernière insertion
		if ($(e.currentTarget.lastChild).attr('value') == $(e.target).attr('value'))
		{
			var fk_socpeople = fullcalendarscheduler_div.find('#contactid').data('fk-socpeople');
			if (fullcalendarscheduler_div.find('#contactid').children('[value='+fk_socpeople+']').length > 0)
				fullcalendarscheduler_div.find('#contactid').val(fk_socpeople).change();
		}
	});
        
	initEventFormFields = function(start, end, event) {
		is_past = moment().unix() > end.unix(); // event dans le passé, il faut donc rendre non éditable certains champs
		is_futur = moment().unix() < start.unix();
		
		fullcalendarscheduler_div.find('input[name^=TFormateurId]').prop('checked', false);
		fullcalendarscheduler_div.find('select[name^=TFormateurHeured]').val(start.format('HH:mm'));
		fullcalendarscheduler_div.find('select[name^=TFormateurHeuref]').val(end.format('HH:mm'));
			
		if (typeof event !== 'undefined')
		{
			//console.log(event);
			
			// Init inputs
			fullcalendarscheduler_div.find('[name=calendrier_type]').val(event.calendrier_type);
			fullcalendarscheduler_div.find('[name=calendrier_status]').val(event.calendrier_status);
			if (typeof event.TFormateur !== 'undefined')
			{
				for (let i in event.TFormateur)
				{
					fullcalendarscheduler_div.find('input[name^=TFormateurId][value='+(event.TFormateur[i].opsid)+']').prop('checked', true);
					fullcalendarscheduler_div.find('select[name="TFormateurHeured['+event.TFormateur[i].opsid+']"]').val(event.TFormateur[i].heured_formated);
					fullcalendarscheduler_div.find('select[name="TFormateurHeuref['+event.TFormateur[i].opsid+']"]').val(event.TFormateur[i].heuref_formated);
				}
			}

			fullcalendarscheduler_div.find('input[name^="TRealHour"]').val(0);
			if (is_futur) fullcalendarscheduler_div.find('input[name^="TRealHour"]').prop('disabled', true);
			if (typeof event.TRealHour !== 'undefined')
			{
				for (let fk_stagiaire in event.TRealHour)
				{
					fullcalendarscheduler_div.find('input[name="TRealHour['+fk_stagiaire+']"]').val(event.TRealHour[fk_stagiaire]);
				}
			}
		}
		else
		{
			// Init des inputs TRealHour
			var duration;
			if (is_past) duration = end.diff(start) / 1000 / 60 / 60; //  /1000 pour avoir des secondes; /60 pour avoir des minutes; /60 pour avoir des heures
			else duration = '';
			
			if (is_futur) fullcalendarscheduler_div.find('input[name^="TRealHour"]').prop('disabled', true);
			else fullcalendarscheduler_div.find('input[name^="TRealHour"]').prop('disabled', false);
			
			fullcalendarscheduler_div.find('.type_hour').val(duration);
		}
		
		// Format en majuscule pour l'objet moment() si non il renvoie le mauvais format
		var date = start.format(fullcalendarscheduler_date_format.toUpperCase());
		fullcalendarscheduler_div.find('#date_start').val(date);
		dpChangeDay('date_start', fullcalendarscheduler_date_format);
		fullcalendarscheduler_div.find('#date_end').val(date);
		dpChangeDay('date_end', fullcalendarscheduler_date_format);
		
        
		var hour_start = start.format('HH');
		var minute_start = start.format('mm');
		fullcalendarscheduler_div.find('#date_starthour').val(hour_start);
		fullcalendarscheduler_div.find('#date_startmin').val(minute_start);
			
		var hour_end = end.format('HH');
		var minute_end = end.format('mm');
		fullcalendarscheduler_div.find('#date_endhour').val(hour_end);
		fullcalendarscheduler_div.find('#date_endmin').val(minute_end);
		
		if (is_past) fullcalendarscheduler_div.find('.is_past input:visible, .is_past select:visible').not('.skip_disabled').prop('disabled', true);
		else fullcalendarscheduler_div.find('.is_past input:visible, .is_past select:visible').not('.skip_disabled').prop('disabled', false);
	};
	
	
});

$.fn.serializeObject = function()
{
	var o = {};
	var a = this.serializeArray();
	$.each(a, function() {
		if (o[this.name] !== undefined) {
			if (!o[this.name].push) {
				o[this.name] = [o[this.name]];
			}
			o[this.name].push(this.value || '');
		} else {
			o[this.name] = this.value || '';
		}
	});
	return o;
};
