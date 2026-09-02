import React from 'react';
import { Head, Link, router } from '@inertiajs/react';
import { create, destroy, edit, show } from '@/routes/posts';

function isEditable(status) {
    return status === 'draft' || status === 'scheduled';
}

function statusClasses(status) {
    switch (status) {
        case 'published':
            return 'bg-green-100 text-green-700';
        case 'scheduled':
            return 'bg-blue-100 text-blue-700';
        case 'draft':
            return 'bg-gray-100 text-gray-700';
        case 'processing':
            return 'bg-yellow-100 text-yellow-700';
        case 'failed':
        case 'partially_failed':
            return 'bg-red-100 text-red-700';
        default:
            return 'bg-gray-100 text-gray-700';
    }
}

export default function Index({ posts }) {
    const handleDelete = (post) => {
        if (!confirm('Delete this post?')) {
            return;
        }

        router.delete(destroy.url(post.id));
    };

    return (
        <>
            <Head title="Posts" />
            <div className="max-w-7xl mx-auto py-10 px-4 sm:px-6 lg:px-8">
                <div className="flex justify-between items-center mb-6">
                    <h1 className="text-2xl font-bold text-gray-900">Posts</h1>
                    <Link
                        href={create.url()}
                        className="bg-blue-600 text-white px-4 py-2 rounded-lg text-sm font-medium hover:bg-blue-700 transition"
                    >
                        + Create Post
                    </Link>
                </div>

                <div className="bg-white shadow rounded-lg divide-y divide-gray-200">
                    {posts.length === 0 ? (
                        <div className="p-6 text-center text-gray-500">
                            No posts yet. Create your first scheduled post.
                        </div>
                    ) : (
                        posts.map((post) => (
                            <div
                                key={post.id}
                                className="p-4 flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between"
                            >
                                <div className="min-w-0 flex-1">
                                    <p className="font-medium text-gray-900 truncate">
                                        {post.content || '(Media only)'}
                                    </p>
                                    <p className="text-xs text-gray-400 mt-1">
                                        {post.scheduled_at
                                            ? new Date(post.scheduled_at).toLocaleString()
                                            : 'Unscheduled'}
                                        {' • '}
                                        {(post.targets || [])
                                            .map((t) => t.social_account?.name)
                                            .filter(Boolean)
                                            .join(', ') || 'No targets'}
                                    </p>
                                </div>
                                <div className="flex items-center gap-2 shrink-0">
                                    <span
                                        className={`px-3 py-1 text-xs rounded-full font-semibold capitalize ${statusClasses(post.status)}`}
                                    >
                                        {post.status.replace('_', ' ')}
                                    </span>
                                    <Link
                                        href={show.url(post.id)}
                                        className="text-sm text-blue-600 hover:underline"
                                    >
                                        View
                                    </Link>
                                    {isEditable(post.status) && (
                                        <>
                                            <Link
                                                href={edit.url(post.id)}
                                                className="text-sm text-gray-700 hover:underline"
                                            >
                                                Edit
                                            </Link>
                                            <button
                                                type="button"
                                                onClick={() => handleDelete(post)}
                                                className="text-sm text-red-600 hover:underline"
                                            >
                                                Delete
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
