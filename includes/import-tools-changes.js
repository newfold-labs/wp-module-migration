//Changes the text of the wordpress to wordpress content in import
    const importer_titles = document.getElementsByClassName("importer-title");
    if( importer_titles ){
        [...importer_titles].forEach((val, index) => {
            if( val?.outerText === 'WordPress'){
                document.getElementsByClassName("importer-title")[index].innerText = migration.wordpress_title
            }
            })
    } 

    // designs a modal for migration tool
    const node = document.createElement("div");
node.innerHTML = `<div class='migrate-screen'> 
<div class='nfd-migration-loading'><span class='nfd-migration-loader'></span><h2 class='nfd-migration-title'>${migration.migration_title}</h2></div> 
${migration.migration_description} 
</div>`;

node.style.position =  "absolute";
node.style.top =  "0";
node.style.bottom =  "0";
node.style.right =  "0";
node.style.left =  "0";
node.style.backgroundColor =  "#ffffff5e";
node.style.display =  "none";
node.style.alignItems = "center";
node.style.justifyContent = "center";
node.setAttribute("id", "migration-progress-modal")

document.getElementById("wpbody-content").appendChild(node)

// load a pop up when user clicks on run importer for wordpress migration tool
    document.querySelector('a[href*="import=site_migration_wordpress_importer"]')?.addEventListener('click', function(e) {
        e.preventDefault(); 
        document.getElementById("migration-progress-modal").style.display = "flex";

        fetch(
           nfdplugin.restApiUrl + "/newfold-migration/v1/migrate/connect&_locale=user",
            {
              credentials: 'same-origin',
              headers: {
                'Content-Type': 'application/json',
                'X-WP-Nonce': nfdplugin.restApiNonce,
              },
            }
          )
        .then((response) => response.json())
       .then(res => {
        fetch(
            nfdplugin.restApiUrl + "/newfold-data/v1/events&_locale=user",
             {
               credentials: 'same-origin',
               method: 'post',
               headers: {
                 'Content-Type': 'application/json',
                 'X-WP-Nonce': nfdplugin.restApiNonce,
               },
               body: JSON.stringify({ 
                action: "migration_initiated_tools",
                category: "user_action",
                data: {
                    page: window.location.href
                }})
             },
           )
        document.getElementById("migration-progress-modal").style.display = "none";
        if(res?.success){
            window.open(res?.data?.redirect_url, "_self")
        }
        // else{
            // alert("please try again in sometime. Thanks!")
        // }
       })
        .catch(err => console.error(err))
       
    });
   