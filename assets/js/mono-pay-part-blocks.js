import { _nx } from '@wordpress/i18n';
import { decodeEntities } from '@wordpress/html-entities';
import React, { useEffect } from 'react';

const { registerPaymentMethod } = window.wc.wcBlocksRegistry
const { getSetting } = window.wc.wcSettings

const settings = getSetting('mono_part_pay_data', {})

const label = decodeEntities(settings.title)

const Content = (props) => {
    const availableParts = settings.available_parts || [];
    const { eventRegistration, emitResponse } = props;
    const { onPaymentSetup } = eventRegistration;

    useEffect(() => {
        const unsubscribe = onPaymentSetup(async () => {
            // Get the value of desired_parts
            const desiredParts = document.getElementById('desired_parts').value;
            const isValid = !!desiredParts; // Check if the value is valid

            if (isValid) {
                return {
                    type: emitResponse.responseTypes.SUCCESS,
                    meta: {
                        paymentMethodData: {
                            desired_parts: desiredParts,
                        },
                    },
                };
            }

            return {
                type: emitResponse.responseTypes.ERROR,
                message: 'Please select the number of desired parts.',
            };
        });

        // Cleanup subscription when component unmounts
        return () => {
            unsubscribe();
        };
    }, [emitResponse.responseTypes.ERROR, emitResponse.responseTypes.SUCCESS, onPaymentSetup]);

    return (
        <div>
            <p>{decodeEntities(settings.description || '')}</p>
            <label htmlFor="desired_parts">{_nx('Desired payments number', 'mono-pay-part')}</label>
            <select name="desired_parts" id="desired_parts">
                {availableParts.map(part => (
                    <option key={part} value={part}>
                        {sprintf(_nx('%d payment', '%d payments', part, 'Number of payments', 'mono-pay-part'), part)}
                    </option>
                ))}
            </select>
        </div>
    );
};

const Icon = () => {
    return settings.icon
        ? <img src={settings.icon} style={{ float: 'right', marginRight: '20px' }} />
        : ''
}

const Label = () => {
    return (
        <span style={{ width: '100%' }}>
            {label}
            <Icon />
        </span>
    )
}
const canMakePayment = () => true;

registerPaymentMethod({
    name: "mono_part_pay",
    label: <Label />,
    content: <Content />,
    edit: <Content />,
    canMakePayment: () => true,
    ariaLabel: label,
    supports: {
        features: settings.supports,
    },
    canMakePayment,
})