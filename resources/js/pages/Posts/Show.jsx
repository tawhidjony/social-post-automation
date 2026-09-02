import React from 'react';
import { Head, Link, router } from '@inertiajs/react';
import { destroy, edit, index } from '@/routes/posts';

export default function Show({ post, editable }) {
    const handleDelete = () => {
        if (!confirm('Delete this post?')) {
            return;
        }

        router.delete(destroy.url(post.id));
    };

    return (
        <>
            <Head title="Post Details" />
            <div className="max-w-3xl mx-auto py-10 px-4 sm:px-6 lg:px-8 space-y-6">
                <div className="flex items-center justify-between gap-4">
                    <div>
                        <Link href={index.url()} className="text-sm text-blue-600 hover:underline">
                            ← Back to Posts
                        </Link>
                        <h1 className="text-2xl font-bold text-gray-900 mt-2">Post Details</h1>
                    </div>
                    {editable && (
                        <div className="flex gap-2">
                            <Link
                                href={edit.url(post.id)}
                                className="bg-gray-100 text-gray-800 px-4 py-2 rounded-lg text-sm font-medium hover:bg-gray-200 transition"
                            >
                                Edit
                            </Link>
                            <button
                                type="button"
                                onClick={handleDelete}
                                className="bg-red-600 text-white px-4 py-2 rounded-lg text-sm font-medium hover:bg-red-700 transition"
                            >
                                Delete
                            </button>
                        </div>
                    )}
                </div>

                <div className="bg-white shadow rounded-lg p-6 space-y-4">
                    <div className="flex items-center justify-between">
                        <span className="text-xs uppercase tracking-wide text-gray-400">Status</span>
                        <span className="text-sm font-semibold capitalize">{post.status.replace('_', ' ')}</span>
                    </div>
                    <div>
                        <span className="text-xs uppercase tracking-wide text-gray-400">Scheduled At</span>
                        <p className="mt-1 text-sm text-gray-800">
                            {post.scheduled_at
                                ? new Date(post.scheduled_at).toLocaleString()
                                : '—'}
                        </p>
                    </div>
                    <div>
                        <span className="text-xs uppercase tracking-wide text-gray-400">Content</span>
                        <p className="mt-1 text-sm text-gray-800 whitespace-pre-wrap">
                            {post.content || '—'}
                        </p>
                    </div>
                    {(post.media || []).length > 0 && (
                        <div>
                            <span className="text-xs uppercase tracking-wide text-gray-400">Media</span>
                            <div className="mt-2 grid grid-cols-2 gap-2">
                                {post.media.map((url) => (
                                    <img
                                        key={url}
                                        src={url}
                                        alt="Post media"
                                        className="w-full h-40 object-cover rounded-lg border"
                                    />
                                ))}
                            </div>
                        </div>
                    )}
                </div>

                <div className="bg-white shadow rounded-lg divide-y divide-gray-200">
                    <div className="p-4">
                        <h2 className="font-semibold text-gray-900">Targets</h2>
                    </div>
                    {(post.targets || []).length === 0 ? (
                        <div className="p-4 text-sm text-gray-500">No targets.</div>
                    ) : (
                        post.targets.map((target) => (
                            <div key={target.id} className="p-4 flex items-center justify-between">
                                <div>
                                    <p className="font-medium text-gray-800">
                                        {target.social_account?.name || 'Unknown account'}
                                    </p>
                                    <p className="text-xs text-gray-400 capitalize">
                                        {target.social_account?.provider}
                                    </p>
                                </div>
                                <span className="text-xs font-semibold capitalize text-gray-600">
                                    {target.status}
                                </span>
                            </div>
                        ))
                    )}
                </div>
            </div>
        </>
    );
}
