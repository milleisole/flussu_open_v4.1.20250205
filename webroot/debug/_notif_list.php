<h4>Notifications list</h4>
<ol id="list"></ol>
<script>
    var eventSource = new EventSource("notifier");
    refreshList();
    eventSource.onmessage = function(event) {
      const params = new URLSearchParams(window.location.search)
      var Sid=params.get('SID');
      var LSid=sessionStorage.getItem("SID");  
      if (LSid!=Sid){
        sessionStorage.clear();
        sessionStorage.setItem("SID",Sid);
      }
      arr=JSON.parse(event.data);
      for (var item in arr) {
        if (item=="SID"){
          // Do Nothing
        } else {
          var tp=arr[item].type;
          var nm=arr[item].name;
          var vl=arr[item].value;
          var id=arr[item].id;
          if (typeof vl === 'object')
            vl=JSON.stringify(vl);
          switch (tp){
            case "N":
              tp="([0] "+nm+"): <strong>"+vl+"</strong>";
              vl="";
              break;
            case 1:
              tp="[1]ALERT:";
              break;
            case 2:
              tp="[2]Counter ("+nm+") INIT:";
              break;
            case 3:
              tp="[3]Counter ("+nm+") VALUE=";
              break;
            case 4:
              tp="[4]Add Row to Chat:";
              break;
            case 5:
              tp="[5]Callback:";
              vl="WID:"+nm+" - BID:"+vl;
              break;
            default:
              tp="[0]Notify "+nm+"=";
          }
          if (id!="SID"){
            var currentdate = new Date(); 
            var recTime=currentdate.getHours().toString().padStart(2, '0')+":"+currentdate.getMinutes().toString().padStart(2, '0')+":"+currentdate.getSeconds().toString().padStart(2, '0');
            var sortDate=currentdate.getMonth().toString().padStart(2, '0')+currentdate.getDay().toString().padStart(2, '0')+recTime;
            sessionStorage.setItem(id,"<!--"+sortDate+"--><i>"+recTime+'</i>&nbsp;-&nbsp;'+tp+' '+vl);
          }
        }
      }
      refreshList();
    };
    function refreshList(){
      var keys=Object.keys(sessionStorage);
      var idxArr=[];
      for (var i=0;i<keys.length;i++){
        if (keys[i]!="SID")
          idxArr.push(sessionStorage.getItem(keys[i]));
      }
      idxArr.sort();
      var lst=document.getElementById("list");
      lst.innerHTML="";
      for (var i = 0; i < idxArr.length; i++){
        lst.innerHTML+=("<li>"+idxArr[i]+"</li>");
      }



      //lst.innerHTML+=("<li><!--<i>"+keys[i]+"</i>&nbsp;-&nbsp;-->"+sessionStorage.getItem(keys[i])+"</li>");
    }
</script>