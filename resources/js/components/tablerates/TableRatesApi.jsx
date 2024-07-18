import React from 'react'


export async function updateTableRate(postData){
    let res = await fetch("/v1/table-rate-shipping/"+postData.id, {
                            method: "POST",
                            body: JSON.stringify(postData),
                            headers: {
                                "Content-type": "application/json"
                            }
                    });
    return res.json();
}

export default function TableRatesApi() {
  return (
    <div>TableRatesApi</div>
  )
}
