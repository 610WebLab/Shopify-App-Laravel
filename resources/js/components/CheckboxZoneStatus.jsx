import React from 'react'
import { useState, useCallback, useEffect } from 'react';
import { Checkbox } from '@shopify/polaris';
import { useToast } from '@shopify/app-bridge-react';

const CheckboxZoneStatus = ({ defaultValue, methodId }) => {
    const { show } = useToast();
    
    const [isChecked, seIstChecked] = useState(defaultValue);
    const handleZoneMethodChange = useCallback((newChecked) =>{ 
        fetch("/updateShipZoneStatus/"+methodId+"?shop=" + Config.shop+"&status="+newChecked).then(res => res.json()).then((result) => {
            console.log("result", result)
            if (result.status) {
                show(result.msg, {duration: 2000})
            }
        }, (error) => {
            show(error, { duration: 2000, isError: true })
        })
        seIstChecked(newChecked) 
    },[]);

    return (
        <div className="check-box">
            <Checkbox checked={isChecked} onChange={handleZoneMethodChange} />
        </div>
    )
}

export default CheckboxZoneStatus
