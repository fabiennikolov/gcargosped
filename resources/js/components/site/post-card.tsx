import { Link } from '@inertiajs/react';
import type { PostCard as PostCardData } from '@/types/site';

/**
 * Mirrors the original markup exactly — the stylesheet only targets these
 * elements as `.post .cover`, `.post .body`, `.post .meta`, `.post .cat` and
 * `.post .read`, so the nesting is load-bearing, not cosmetic.
 */
export default function PostCard({ post }: { post: PostCardData }) {
    return (
        <Link className="glass post reveal" href={post.url}>
            <div className="cover">
                {post.coverImage ? (
                    <img src={post.coverImage} alt={post.title} loading="lazy" decoding="async" />
                ) : (
                    <svg viewBox="0 0 400 225" preserveAspectRatio="xMidYMid slice" aria-hidden="true">
                        <rect width="400" height="225" fill="#E6F8EF" />
                        <path
                            d="M-20 180 C 100 110, 260 240, 420 140"
                            fill="none"
                            stroke="#0E9F6E"
                            strokeWidth="3"
                            strokeDasharray="8 8"
                            opacity=".6"
                        />
                        <circle cx="70" cy="156" r="9" fill="#0B8358" />
                        <circle cx="330" cy="122" r="7" fill="#171B23" />
                    </svg>
                )}
            </div>

            <div className="body">
                <div className="meta">
                    {post.category && <span className="cat">{post.category}</span>}
                    {post.publishedAt && <span>{post.publishedAt}</span>}
                    {post.readMinutes > 0 && <span>· {post.readMinutes} мин</span>}
                </div>

                <h3>{post.title}</h3>
                <p>{post.excerpt}</p>
                <span className="read">Прочети →</span>
            </div>
        </Link>
    );
}
