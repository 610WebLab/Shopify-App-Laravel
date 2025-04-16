import React, { useState, useCallback, useEffect } from 'react';
import { Checkbox } from '@shopify/polaris';
import { useToast } from '@shopify/app-bridge-react';
const CheckBoxOtherCarrierServices = ({ defaultValue, methodId }) => {
    const { show } = useToast();
    
    const [isChecked, seIstChecked] = useState(defaultValue);
    const handleZoneMethodChange = useCallback((newChecked) => { 
        const status = newChecked ? 'active' : 'inactive';
        fetch("/update-other-carrier-status/"+methodId+"?shop=" + Config.shop+"&status="+status).then(res => res.json()).then((result) => {
            console.log("result", result)
            if (result.status) {
                show(result.message, {duration: 2000})
            } else {
                show(result.message, { duration: 2000, isError: true })
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

export default CheckBoxOtherCarrierServices
