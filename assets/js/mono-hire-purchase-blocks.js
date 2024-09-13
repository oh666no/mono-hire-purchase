import { _nx } from '@wordpress/i18n';
import { decodeEntities } from '@wordpress/html-entities';
import React, { useEffect } from 'react';

const { registerPaymentMethod } = window.wc.wcBlocksRegistry;
const { getSetting } = window.wc.wcSettings;

const settings = getSetting('mono_hire_purchase_data', {});
const label = decodeEntities(settings.title);

const Content = ({ eventRegistration, emitResponse }) => {
    const availableParts = settings.available_parts || [];
    const { onPaymentSetup } = eventRegistration;

    useEffect(() => {
        const unsubscribe = onPaymentSetup(async () => {
            const desiredParts = document.getElementById('desired_parts').value;
            const isValid = Boolean(desiredParts);

            if (isValid) {
                return {
                    type: emitResponse.responseTypes.SUCCESS,
                    meta: {
                        paymentMethodData: { desired_parts: desiredParts },
                    },
                };
            }

            return {
                type: emitResponse.responseTypes.ERROR,
                message: 'Please select the number of desired parts.',
            };
        });

        // Cleanup subscription when component unmounts
        return unsubscribe;
    }, [emitResponse.responseTypes, onPaymentSetup]);

    return (
        <div>
            <p>{decodeEntities(settings.description || '')}</p>
            <label htmlFor="desired_parts">
                {_nx('Desired payments number', 'mono-pay-part')}
            </label>
            <select name="desired_parts" id="desired_parts">
                {availableParts.map((part) => (
                    <option key={part} value={part}>
                        {sprintf(
                            _nx('%d payment', '%d payments', part, 'Number of payments', 'mono-pay-part'),
                            part
                        )}
                    </option>
                ))}
            </select>
        </div>
    );
};

const Label = () => (
    <span style={{ width: '100%' }}>
        {label}
        {settings.icon && (
            <img
                src={settings.icon}
                style={{ float: 'right', marginRight: '20px' }}
                alt="Payment Icon"
            />
        )}
    </span>
);

const canMakePayment = () => true;

registerPaymentMethod({
    name: 'mono_hire_purchase',
    label: <Label />,
    content: <Content />,
    edit: <Content />,
    canMakePayment,
    ariaLabel: label,
    supports: {
        features: settings.supports,
    },
});