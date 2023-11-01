shoes=0

function go(id){
	u=self.location.href.toString()
	u=u.split('?')
	
	if(shoes==0){cnf=confirm('No shoes. Are you sure?');if(!cnf){return false}}

	u='go.php'+'?id='+id+'&shoes='+shoes+'&name='+encodeURIComponent(document.getElementById('inp'+id).value.trim())+'&desc='+encodeURIComponent(document.getElementById('tx').value.trim())+'&auth='+base
	console.log(u)
	self.location.href=u
}

function res_butts(){
	elements = document.querySelectorAll("input[type=button]")
	for(i in elements){
		if(elements[i].value && elements[i].value.trim()!='GO'){
			elements[i].style.color='#000'
			elements[i].style.backgroundColor='#eee'
		}
		if(elements[i].value && elements[i].value.trim()=='GO'){elements[i].style.opacity=1}
	}
	
}