jQuery(function($){
	var container = $(CustomWpZapier.container);
	var fieldsTable = container.find("#wp-custom-zapier-field-mappings tbody");
	var dataService = CustomWpZapier.dataService;
	var mappingForm = container.find(".wp-custom-zapier-field-mappings-form");
	var btnAddMapping  = container.find("#add-mapping");
	container.on("click", "#submit", function(){
		var data = getData();
		dataService.post('/save-settings', data.data).done(function(data){
			if(data.navigate == 1)
			{
				navigate(data.url);
			}
		});
		return false;
	});

	container.on("keyup", "#search", function()
	{
		var search = $(this).val().toLowerCase();
		if(!search){
			fieldsTable.find("tr").removeClass("hidden");
			return;
		}

		fieldsTable.find('tr').each(function(){
		
			if($(this).text().toLowerCase().indexOf(search.toLowerCase()) > -1)
			{
				$(this).removeClass("hidden");
			}
			else
			{
				$(this).addClass("hidden");
			}
		});
	});

	container.on("click", "#save-mapping", function()
	{
		if(!validate())
		{
			return;
		}
		var data = {
			"ApiFieldName": mappingForm.find("#wp_zapier_form_api_field").val(),
			"WpFieldName": mappingForm.find("#wp_zapier_form_wp_field").val(),
			"Type" : mappingForm.find("#wp_zapier_form_field_type").val()
		};
		dataService.post("/save-mapping", data).done(function(){
			mappingForm.addClass('hidden');
			btnAddMapping.removeClass("hidden");
			loadMappings();
		});		
	});

	btnAddMapping.on("click", function(){
		mappingForm.removeClass('hidden');
		btnAddMapping.addClass("hidden");
	})

	loadMappings();

	mappingForm.on("keyup", "input", function(){
		validate();
	});

	function validate()
	{
		var valid = true;
		if(!mappingForm.find("#wp_zapier_form_api_field").val())
		{
			mappingForm.find("#wp_zapier_form_api_field").parent(".form-group").addClass("error");
			valid = false;
		}
		else
		{
			mappingForm.find("#wp_zapier_form_api_field").parent(".form-group").removeClass("error");
		}
		if(!mappingForm.find("#wp_zapier_form_wp_field").val())
		{
			mappingForm.find("#wp_zapier_form_wp_field").parent(".form-group").addClass("error");
			valid = false;
		}
		else
		{
			mappingForm.find("#wp_zapier_form_wp_field").parent(".form-group").removeClass("error");
		}
		return valid;
	}

	function loadMappings()
	{
		dataService.get("/get-mappings", {}, true).done(function(data){
			if(!data.Mappings)
			{
				return;
			}
			fieldsTable.find("tr").remove();
			populateTable(data.Mappings);			
		});
	}

	function populateTable(mappings){
		if(mappings.post)
		{
			populateFields(mappings.post, "Post");
		}	
		if(mappings.schedule)
		{
			populateFields(mappings.schedule, "Schedule");
		}
		if(mappings.taxonomy)
		{
			populateFields(mappings.taxonomy, "Taxonomy");
		}
		if(mappings.meta)
		{
			populateFields(mappings.meta, "Meta");
		}	
	}
 
	function populateFields(fields, type)
	{ 
		for(var f in fields)
		{
			field = {};
			if(!isNaN(parseInt(f)))
			{
				field.Name = fields[f];
				field.MappedTo = "_work_hours";
			}
			else
			{
				field.Name = f;
				field.MappedTo = fields[f];
			}
			field.Type = type;			
			fieldsTable.append(buildRow(field));
		}
	}

	function buildRow(field)
	{
		var tr = $("<tr><td>" + [
			field.Name, 
			field.MappedTo, 
			field.Type, 
			'<button class="pt-5 float-right button button-primary"><span class="dashicons dashicons-edit-large"></span></button>',
			'<button class="pt-5 float-right button button-secondary"><span class="dashicons dashicons-trash"></span></button>'
		].join("</td><td>") + "</td></tr>");
		tr.find(".button-primary").on('click', function(){
			mappingForm.removeClass('hidden');
			mappingForm.find(".search-bar").addClass("hidden");
			mappingForm.find("#wp_zapier_form_api_field").val(field.Name);
			mappingForm.find("#wp_zapier_form_wp_field").val(field.MappedTo);
			mappingForm.find("#wp_zapier_form_field_type").val(field.Type.toLowerCase());
		});
		tr.find(".button-secondary").on('click', function()
		{
			dataService.delete("/save-mapping?ApiFieldName=" + field.Name + "&Type=" + field.Type.toLowerCase()).done(function(){
				tr.remove();
			});
		});

		return tr;
	}

	function getData()
	{
		return {
			data: {
				'security_key': container.find("input#security_key").val()
			}
		};
	}
});