"""create perf_feedback table

Revision ID: e01fe3adad2e
Revises: 
Create Date: 2022-02-07 09:20:06.266366

"""
from alembic import op
import sqlalchemy as sa


# revision identifiers, used by Alembic.
revision = 'e01fe3adad2e'
down_revision = None
branch_labels = None
depends_on = None


def upgrade():
    op.create_table(
        'perf_feedback',
        sa.Column('id', sa.Integer, primary_key=True),
        sa.Column('rating', sa.Integer, nullable=False), 
        sa.Column('comment', sa.String(1200), nullable=False),
        sa.Column('datetime', sa.DateTime(), nullable=False)
    )


def downgrade():
    op.drop_table('perf_feedback')
