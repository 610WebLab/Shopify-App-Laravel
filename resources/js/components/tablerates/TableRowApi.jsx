import React from 'react'


export async function getTableRow(table_rate_id){
  let res = await fetch("/v1/table-rates-rows?table_rate_id="+table_rate_id);
  return res.json();
}

export async function addTableRow(postData){
    let res = await fetch("/v1/table-rates-rows", {
                            method: "POST",
                            body: JSON.stringify(postData),
                            headers: {
                                "Content-type": "application/json"
                            }
                    });
    return res.json();
}


export async function updateTableRow(postData){
  let res = await fetch("/v1/table-rates-rows/"+postData.id, {
                          method: "POST",
                          body: JSON.stringify(postData),
                          headers: {
                              "Content-type": "application/json"
                          }
                  });
  return res.json();
}

export async function deleteTableRow(postData){
  let res = await fetch("/v1/table-rates-rows/"+postData.ids[0], {
                          method: "POST",
                          body: JSON.stringify(postData),
                          headers: {
                              "Content-type": "application/json"
                          }
                  });
  return res.json();
}

export default function TableRowApi() {
  return (
    <div>TableRowApi</div>
  )
}
