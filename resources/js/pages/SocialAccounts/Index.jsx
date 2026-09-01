import React from 'react';
import { Head, Link } from '@inertiajs/react';
import { redirect } from "@/routes/social";

export default function Index({ accounts }) {


    console.log('Accounts:', accounts); // Debugging line to check the accounts data

    return (
        <>
            <Head title="Social Accounts" />
            <div className="max-w-7xl mx-auto py-10 px-4 sm:px-6 lg:px-8">
                <div className="flex justify-between items-center mb-6">
                    <h1 className="text-2xl font-bold text-gray-900">Connected Accounts</h1>
                    
                    <div className="flex gap-2">

<button
    onClick={() => window.location.href = redirect({ provider: 'facebook' }).url}
    className="bg-blue-600 text-white px-4 py-2 rounded-lg text-sm font-medium hover:bg-blue-700 transition"
>
    + Connect Facebook Page
</button>


                    </div>
                </div>

                <div className="bg-white shadow rounded-lg divide-y divide-gray-200">
                    {accounts.length === 0 ? (
                        <div className="p-6 text-center text-gray-500">No social accounts connected yet.</div>
                    ) : (
                        accounts.map((account) => (
                            <div key={account.id} className="p-4 flex items-center justify-between">
                                <div className="flex items-center gap-4">
                                    <img
                                        src={account.avatar_url || 'https://via.placeholder.com/40'}
                                        alt={account.name}
                                        className="w-10 h-10 rounded-full object-cover"
                                    />
                                    <div>
                                        <h4 className="font-semibold text-gray-800">{account.name}</h4>
                                        <p className="text-xs text-gray-400 capitalize">{account.provider} • ID: {account.provider_account_id}</p>
                                    </div>
                                </div>
                                <div>
                                    <span className={`px-3 py-1 text-xs rounded-full font-semibold ${
                                        account.is_active ? 'bg-green-100 text-green-700' : 'bg-red-100 text-red-700'
                                    }`}>
                                        {account.is_active ? 'Connected' : 'Disconnected'}
                                    </span>
                                </div>
                            </div>
                        ))
                    )}
                </div>
            </div>
        </>
    );
}