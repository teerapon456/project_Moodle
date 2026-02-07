import { NextResponse } from 'next/server';
import { portalDb } from '@/lib/db';
import { RowDataPacket } from 'mysql2';

export async function GET() {
    try {
        const [rows] = await portalDb.query<RowDataPacket[]>(
            'SELECT COUNT(*) as count FROM users WHERE is_active = 1'
        );

        const count = rows[0]?.count || 0;

        return NextResponse.json({
            totalUsers: count
        });
    } catch (error) {
        console.error('Database Error:', error);
        return NextResponse.json(
            { error: 'Failed to fetch user stats' },
            { status: 500 }
        );
    }
}
