import React from 'react';
import { Head, Link } from '@inertiajs/react';
import { redirect } from "@/routes/social";

export default function Index({ accounts }) {


    console.log('Accounts:', accounts); // Debugging line to check the accounts data

    return (
        <>
            <Head title="Social Accounts" />
            <div className="py-10 px-4 sm:px-6 lg:px-8">
                <div className="flex items-center justify-between mb-md">
                    <div className="flex items-center gap-sm">
                        {/* Used text-foreground to match standard shadcn headers */}
                        <h2 className="text-headline-md text-foreground">Active Channels</h2>

                        {/* Changed text-card to text-secondary-foreground for standard badge color-contrast structure */}
                        <span className="px-sm py-xs rounded-full bg-secondary text-secondary-foreground text-label-md">
                            4
                        </span>
                    </div>

                    {/* Replaced text-on-surface-variant with text-muted-foreground and fixed the icon token color */}
                    <div className="flex items-center gap-xs text-muted-foreground text-metadata-sm">
                        <span className="material-symbols-outlined text-[16px] text-primary">
                            schedule
                        </span>
                        <span>Auto-refresh enabled (every 15m)</span>
                    </div>
                </div>


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
                                    <span className={`px-3 py-1 text-xs rounded-full font-semibold ${account.is_active ? 'bg-green-100 text-green-700' : 'bg-red-100 text-red-700'
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