import React from 'react';
import { Head, router } from '@inertiajs/react';
import { redirect } from '@/routes/social';
import { destroy, update } from '@/routes/social-accounts';

const providerLabels = {
    facebook: 'Facebook Page',
    linkedin: 'LinkedIn',
    twitter: 'Twitter / X',
};

export default function Index({ accounts, canManage = false, providers = ['facebook', 'linkedin', 'twitter'] }) {
    const handleConnect = (provider) => {
        window.location.href = redirect({ provider }).url;
    };

    const handleToggle = (account) => {
        if (!canManage) {
            return;
        }

        router.patch(update.url(account.id), {
            is_active: !account.is_active,
        });
    };

    const handleDisconnect = (account) => {
        if (!canManage) {
            return;
        }

        if (!confirm(`Disconnect ${account.name}?`)) {
            return;
        }

        router.delete(destroy.url(account.id));
    };

    return (
        <>
            <Head title="Social Accounts" />
            <div className="max-w-7xl mx-auto py-10 px-4 sm:px-6 lg:px-8">
                <div className="flex flex-col gap-4 sm:flex-row sm:justify-between sm:items-center mb-6">
                    <h1 className="text-2xl font-bold text-gray-900">Connected Accounts</h1>

                    {canManage && (
                        <div className="flex flex-wrap gap-2">
                            {providers.map((provider) => (
                                <button
                                    key={provider}
                                    type="button"
                                    onClick={() => handleConnect(provider)}
                                    className="bg-blue-600 text-white px-4 py-2 rounded-lg text-sm font-medium hover:bg-blue-700 transition"
                                >
                                    + Connect {providerLabels[provider] || provider}
                                </button>
                            ))}
                        </div>
                    )}
                </div>

                <div className="bg-white shadow rounded-lg divide-y divide-gray-200">
                    {accounts.length === 0 ? (
                        <div className="p-6 text-center text-gray-500">
                            No social accounts connected yet.
                        </div>
                    ) : (
                        accounts.map((account) => (
                            <div
                                key={account.id}
                                className="p-4 flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between"
                            >
                                <div className="flex items-center gap-4">
                                    <img
                                        src={account.avatar_url || 'https://via.placeholder.com/40'}
                                        alt={account.name}
                                        className="w-10 h-10 rounded-full object-cover"
                                    />
                                    <div>
                                        <h4 className="font-semibold text-gray-800">{account.name}</h4>
                                        <p className="text-xs text-gray-400 capitalize">
                                            {account.provider}
                                            {account.username ? ` • @${account.username}` : ''}
                                            {' • '}ID: {account.provider_account_id}
                                        </p>
                                    </div>
                                </div>
                                <div className="flex items-center gap-2">
                                    <span
                                        className={`px-3 py-1 text-xs rounded-full font-semibold ${
                                            account.is_active
                                                ? 'bg-green-100 text-green-700'
                                                : 'bg-red-100 text-red-700'
                                        }`}
                                    >
                                        {account.is_active ? 'Connected' : 'Disconnected'}
                                    </span>
                                    {canManage && (
                                        <>
                                            <button
                                                type="button"
                                                onClick={() => handleToggle(account)}
                                                className="inline-flex items-center px-3 py-1.5 rounded-md text-xs font-medium bg-gray-100 text-gray-800 hover:bg-gray-200 transition"
                                            >
                                                {account.is_active ? 'Deactivate' : 'Activate'}
                                            </button>
                                            <button
                                                type="button"
                                                onClick={() => handleDisconnect(account)}
                                                className="inline-flex items-center px-3 py-1.5 rounded-md text-xs font-medium bg-red-600 text-white hover:bg-red-700 transition"
                                            >
                                                Disconnect
                                            </button>
                                        </>
                                    )}
                                </div>
                            </div>
                        ))
                    )}
                </div>
            </div>
        </>
    );
}
