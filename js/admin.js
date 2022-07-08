jQuery(function($){
	var container = $(CustomWpZapier.container);
	var fieldsTable = container.find("#wp-custom-zapier-field-mappings");
	var dataService = CustomWpZapier.dataService;
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
		fieldsTable.find('tbody tr').each(function(){
			if($(this).text().toLowerCase().indexOf(search) > -1)
			{
				$(this).show();
			}
			else
			{
				$(this).hide();
			}
		});
	});

	loadMappings();

	function loadMappings()
	{
		dataService.get("/get-mappings", {}, true).done(function(data){
			if(!data.Mappings)
			{
				return;
			} 
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
			console.log(field, 'edi');
		});
		tr.find(".button-secondary").on('click', function(){
			console.log(field, 'delete');
		})
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